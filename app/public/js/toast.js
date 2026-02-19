document.querySelectorAll('.toast').forEach(toast => {
    setTimeout(() => {
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 500);
    }, 4000);
});
