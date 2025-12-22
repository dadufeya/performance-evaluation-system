// Simple form validation example
function validateForm(formId){
const form = document.getElementById(formId);
let valid = true;
form.querySelectorAll('[required]').forEach(input => {
if(!input.value.trim()){
valid = false;
input.style.borderColor = 'red';
} else {
input.style.borderColor = '';
}
});
return valid;
}