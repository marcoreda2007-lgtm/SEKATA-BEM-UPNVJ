const depositPhotos = document.getElementById('deposit-photos');

if (depositPhotos) {
    const preview = document.getElementById('photos-preview');
    const error = document.getElementById('photos-error');
    let files = [];
    let previewUrls = [];

    function renderPhotos() {
        previewUrls.forEach((url) => URL.revokeObjectURL(url));
        previewUrls = [];
        preview.replaceChildren();

        const selection = new DataTransfer();
        files.forEach((file) => selection.items.add(file));
        depositPhotos.files = selection.files;

        files.forEach((file, index) => {
            const column = document.createElement('div');
            column.className = 'col-6 col-md-3';
            const image = document.createElement('img');
            const url = URL.createObjectURL(file);
            previewUrls.push(url);
            image.src = url;
            image.alt = file.name;
            image.width = 120;
            image.height = 100;
            image.className = 'img-fluid rounded object-fit-cover';

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn btn-outline-danger btn-sm d-block mt-2';
            remove.textContent = `Hapus foto ${index + 1}`;
            remove.addEventListener('click', () => {
                files.splice(index, 1);
                renderPhotos();
                depositPhotos.focus();
            });
            column.append(image, remove);
            preview.append(column);
        });
    }

    depositPhotos.addEventListener('change', () => {
        files = Array.from(depositPhotos.files);
        error.textContent = '';

        if (files.length > 5 || files.some((file) => file.size > 5 * 1024 * 1024
            || !['image/jpeg', 'image/png'].includes(file.type))) {
            error.textContent = 'Pilih maksimal 5 foto JPG/PNG, masing-masing maksimal 5 MB.';
            files = [];
        }

        renderPhotos();
    });
}
