const views = document.querySelectorAll('.add-container'),
addresses = document.querySelectorAll('.addR');

views.forEach((view, index) => {
    view.addEventListener('click', () => {
        addresses[index].classList.toggle('d-none');
    });
});