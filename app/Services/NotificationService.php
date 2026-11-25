<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Notification;
use App\Models\User;
use InvalidArgumentException;

class NotificationService
{
    /**
     * Dipanggil Nabil (F3) di dalam DB::transaction setelah pengajuan Menunggu tersimpan.
     * Mengirim notifikasi ke semua akun admin (Biro SPPI).
     */
    public static function notifyBookingCreated(Booking $booking): void
    {
        if ($booking->status !== 'Menunggu') {
            throw new InvalidArgumentException(
                "Status booking harus 'Menunggu' untuk notifikasi pengajuan baru."
            );
        }

        $booking->loadMissing('user');

        $unitName = $booking->user?->unit_name ?? 'Unit Peminjam';
        $schedule = self::formatSchedule($booking);

        $adminIds = User::query()
            ->where('role', 'admin')
            ->where('id', '!=', $booking->user_id)
            ->pluck('id');

        foreach ($adminIds as $adminId) {
            Notification::create([
                'user_id' => $adminId,
                'booking_id' => $booking->id,
                'type' => Notification::TYPE_BOOKING_CREATED,
                'title' => 'Pengajuan Peminjaman Baru',
                'message' => "{$unitName} mengajukan peminjaman sekretariat untuk {$schedule}.",
            ]);
        }
    }

    /**
     * Dipanggil Reda (F5) di dalam DB::transaction setelah SPPI menyetujui atau menolak.
     * Mengirim notifikasi ke unit pemilik pengajuan.
     */
    public static function notifyBookingDecision(Booking $booking): Notification
    {
        $schedule = self::formatSchedule($booking);
        $reason = trim((string) $booking->decision_reason);
        $reasonSuffix = $reason !== '' ? " Alasan: {$reason}" : '';

        if ($booking->status === 'Disetujui') {
            return Notification::create([
                'user_id' => $booking->user_id,
                'booking_id' => $booking->id,
                'type' => Notification::TYPE_BOOKING_APPROVED,
                'title' => 'Pengajuan Disetujui',
                'message' => "Pengajuan sekretariat untuk {$schedule} telah disetujui SPPI.{$reasonSuffix}",
            ]);
        }

        if ($booking->status === 'Ditolak') {
            return Notification::create([
                'user_id' => $booking->user_id,
                'booking_id' => $booking->id,
                'type' => Notification::TYPE_BOOKING_REJECTED,
                'title' => 'Pengajuan Ditolak',
                'message' => "Pengajuan sekretariat untuk {$schedule} ditolak SPPI.{$reasonSuffix}",
            ]);
        }

        throw new InvalidArgumentException(
            "Status booking harus 'Disetujui' atau 'Ditolak' untuk notifikasi keputusan."
        );
    }

    /**
     * Dipanggil Raehansyah (F6) di dalam DB::transaction setelah SPPI memeriksa bukti pengembalian.
     * Mengirim notifikasi ke unit pemilik pengajuan.
     */
    public static function notifyProofReviewed(Booking $booking): Notification
    {
        $schedule = self::formatSchedule($booking);
        $note = trim((string) $booking->inspection_note);
        $noteSuffix = $note !== '' ? " Catatan SPPI: {$note}" : '';

        if ($booking->status === 'Perlu Perbaikan') {
            return Notification::create([
                'user_id' => $booking->user_id,
                'booking_id' => $booking->id,
                'type' => Notification::TYPE_PROOF_REVISION_REQUESTED,
                'title' => 'Bukti Pengembalian Perlu Perbaikan',
                'message' => "Bukti pengembalian sekretariat untuk {$schedule} perlu diperbaiki.{$noteSuffix}",
            ]);
        }

        if ($booking->status === 'Selesai') {
            return Notification::create([
                'user_id' => $booking->user_id,
                'booking_id' => $booking->id,
                'type' => Notification::TYPE_PROOF_COMPLETED,
                'title' => 'Peminjaman Selesai',
                'message' => "Bukti pengembalian sekretariat untuk {$schedule} telah diterima SPPI. Peminjaman selesai.{$noteSuffix}",
            ]);
        }

        throw new InvalidArgumentException(
            "Status booking harus 'Perlu Perbaikan' atau 'Selesai' untuk notifikasi pemeriksaan bukti."
        );
    }

    private static function formatSchedule(Booking $booking): string
    {
        $tanggal = $booking->tanggal
            ? $booking->tanggal->format('d M Y')
            : '-';

        $start = substr((string) $booking->start_time, 0, 5);
        $end = substr((string) $booking->end_time, 0, 5);

        return "{$tanggal}, {$start}â€“{$end} WIB";
    }
}