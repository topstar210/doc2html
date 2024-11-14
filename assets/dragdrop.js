function allowDrop(ev) {
    ev.preventDefault();
}

function drag(ev) {
    ev.dataTransfer.setData("image", ev.target.id);
}

function drop(ev) {
    ev.preventDefault();
    const data = ev.dataTransfer.getData("image");
    const selected = document.getElementById(data);
    const selectedParent = selected.closest('.new-photo');

    const target = ev.target;
    const targetParent = target.closest('.article-photo');

    const formData = new FormData();
    formData.append('action', 'file_move');
    formData.append('oldpath', target.src);
    formData.append('newpath', selected.src);
    
    fetch('./upload.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            selected.src = ev.target.src + "?v="+Date.now();
            targetParent.innerHTML = selectedParent.innerHTML;

            selectedParent.remove();
        })
        .catch(error => {
            console.error('error:', error);
        });

}