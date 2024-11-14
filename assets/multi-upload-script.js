window.addEventListener('load', function () {
    const fileDropZone = document.getElementById('fileDropZone');
    const fileListPreview = document.getElementById('fileListPreview');
    const uploadBtn = document.getElementById('uploadBtn');

    let files = []; // Array to hold selected files

    if (fileDropZone) {
        // Highlight drop area on drag events
        ['dragenter', 'dragover'].forEach(event => {
            fileDropZone.addEventListener(event, (e) => {
                e.preventDefault();
                e.stopPropagation();
                fileDropZone.style.backgroundColor = '#f0f0f0';
            });
        });

        ['dragleave', 'drop'].forEach(event => {
            fileDropZone.addEventListener(event, (e) => {
                e.preventDefault();
                e.stopPropagation();
                fileDropZone.style.backgroundColor = '';
            });
        });

        // Handle file selection through drag-and-drop
        fileDropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            const droppedFiles = Array.from(e.dataTransfer.files);
            handleFiles(droppedFiles);
        });

        // Handle file selection through click (optional)
        fileDropZone.addEventListener('click', () => {
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.multiple = true; // Allow multiple file selection
            fileInput.onchange = (e) => {
                const selectedFiles = Array.from(e.target.files);
                handleFiles(selectedFiles);
            };
            fileInput.click();
        });
    }

    // Handle the file processing
    function handleFiles(selectedFiles) {
        files.push(...selectedFiles); // Add new files to the array
        updateFilePreview();
        uploadBtn.disabled = false; // Enable the upload button
    }

    // Update the file preview area with selected files
    function updateFilePreview() {
        fileListPreview.innerHTML = ''; // Clear previous preview

        files.forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.textContent = `${index + 1}. ${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
            fileListPreview.appendChild(fileItem);
        });
    }

    if (uploadBtn) {
        // Handle file upload on button click
        uploadBtn.addEventListener('click', () => {
            if (files.length === 0) return;

            const formData = new FormData();
            files.forEach(file => {
                formData.append('files[]', file);
            });


            // Simulating a file upload with fetch
            fetch('./upload.php', {
                method: 'POST',
                body: formData,
            })
                .then(response => response.json())
                .then(data => {
                    let divHtm = '<div class="flex flex-wrap">';
                    for (let i = 0; i < data.length; i++) {
                        const file = data[i];
                        divHtm += `
                            <div style="width:calc(31% - 20px)" class="new-photo">
                                <img src="${file['path']}" id="new_photo_${i}" draggable="true" ondragstart="drag(event)" alt="image" loading="lazy" class="img-responsive">
                                <div>
                                    ${file['info']['width']}×${file['info']['height']}px 
                                    <span class="${file['info']['width'] > warningSize ? "img_ok" : "img_no"}">
                                        [${file['info']['width'] > warningSize ? "OK" : "NO"}]
                                    </span>
                                </div>
                            </div>
                        `;
                    }
                    divHtm += '</div>';
                    // console.log(divHtm);
                    const parent = uploadBtn.closest('.file-drop-area'); // Get the parent with class 'file-drop-area'
                    if (parent) {
                        parent.innerHTML = "";
                        parent.innerHTML += divHtm; // Append the generated div to the parent element
                    }
                    // alert('Files uploaded successfully!');
                    // fileListPreview.textContent = 'No files selected';
                    // uploadBtn.disabled = true; // Disable upload button after upload
                    // files = []; // Reset files array
                })
                .catch(error => {
                    console.error('Upload error:', error);
                    // alert('Failed to upload files.');
                });
        });
    }

});
