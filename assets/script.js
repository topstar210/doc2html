const filePreview = document.getElementById('filePreview');

window.addEventListener('load', function () {
    const dropArea = document.getElementById('dropArea');
    const fileInput = document.getElementById('fileInput');
    const submitBtn = document.getElementById('submitBtn');

    let file; // This variable will hold the uploaded file

    if (dropArea) {
        // Drag-and-drop events for highlighting
        ['dragenter', 'dragover'].forEach(event => {
            dropArea.addEventListener(event, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropArea.style.backgroundColor = '#f0f0f0';
            });
        });

        ['dragleave', 'drop'].forEach(event => {
            dropArea.addEventListener(event, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropArea.style.backgroundColor = '';
            });
        });

        // Handle file selection through drag-and-drop
        dropArea.addEventListener('drop', (e) => {
            e.preventDefault();
            file = e.dataTransfer.files[0];
            if (file) {
                updateFileInput(file);
            }
        });

        // Handle file selection through clicking
        dropArea.addEventListener('click', () => {
            fileInput.click();
        });

        // Update file preview and submit button when a file is selected via input
        fileInput.addEventListener('change', (e) => {
            file = e.target.files[0];
            if (file) {
                updateFilePreview(file);
                submitBtn.disabled = false;
            }
        });
    }

});

// Update the file input and preview when a file is dropped
function updateFileInput(droppedFile) {
    // Use DataTransfer to simulate the dropped file in the file input
    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(droppedFile);
    fileInput.files = dataTransfer.files;

    updateFilePreview(droppedFile);
    submitBtn.disabled = false;
}

// Update the file preview area
function updateFilePreview(file) {
    filePreview.textContent = `Selected file: ${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
}

// main functions
function markForRemoval(index) {
    const removeIndices = document.getElementById('removeIndices');
    let indices = removeIndices.value ? removeIndices.value.split(',') : [];
    if (!indices.includes(index.toString())) {
        indices.push(index);
        removeIndices.value = indices.join(',');
        document.getElementById(`section-${index}`).remove();;
    }
}

function toggleStrong(index, type) {
    const checkbox = document.getElementById(`${type}-${index}`);

    // Apply or remove the strong tag in the HTML based on the checkbox status
    const textElement = document.getElementById(`text_${index}`);
    textElement.innerHTML = textElement.innerHTML.replace(/<strong>/g, '').replace(/<\/strong>/g, '');
    if (checkbox.checked && type === 'title') {
        textElement.innerHTML = `<strong>${textElement.innerHTML}</strong>`;
    }
}

function replaceImgUrl(path) {
    return path.replace("temp_extract/word/media", "assets");
}

function saveDocument() {
    const elements = document.querySelectorAll('.section > .block, .section > .flex');

    const htmlContents = Array.from(elements).map(element => {
        const imgWrapper = document.createElement('p');
        const articlePhotoWraps = element.querySelectorAll(".article-photo");
        articlePhotoWraps.forEach(wrap => {
            const images = wrap.querySelectorAll('img');
            images.forEach(img => {
                img.removeAttribute('style');
                img.removeAttribute('id');
                img.removeAttribute('ondragstart');
                img.removeAttribute('draggable');
                img.src = replaceImgUrl(img.src);
                imgWrapper.appendChild(img.cloneNode(true));
            });
            wrap.remove();
        })
        if(imgWrapper.querySelectorAll('img').length){
            element.appendChild(imgWrapper);
        }

        const paragraphs = element.querySelectorAll('p');
        paragraphs.forEach(p => {
            p.removeAttribute('class');
            p.removeAttribute('id');
            // Merge adjacent <strong> tags
            let currentStrong = null;
            Array.from(p.childNodes).forEach(node => {
                if (node.nodeName === 'STRONG') {
                    if (currentStrong) {
                        currentStrong.innerHTML += node.innerHTML;
                        node.remove();
                    } else {
                        currentStrong = node;
                    }
                } else {
                    currentStrong = null;
                }
            });
        });
        return element.innerHTML;
    });
    const result = htmlContents.join('');
    // console.log(result);
    const docRes = document.getElementById('docmentRes');
    docRes.value = JSON.stringify(result);

    document.getElementById('save').value = "save";
    document.getElementById('doc-home').submit();
}