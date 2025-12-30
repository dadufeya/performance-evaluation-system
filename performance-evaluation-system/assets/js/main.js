// Generic JS for toggling sidebar or future features
document.addEventListener('DOMContentLoaded', () => {
const toggleBtn = document.querySelector('.toggle-sidebar');
if(toggleBtn){
toggleBtn.addEventListener('click', () => {
document.querySelector('.sidebar').classList.toggle('collapsed');
});
}
});