// Example chart using Chart.js
function renderPerformanceChart(teacherName, questions, scores){
const ctx = document.getElementById('performanceChart').getContext('2d');
new Chart(ctx, {
type: 'bar',
data: {
labels: questions,
datasets: [{
label: teacherName + ' Performance',
data: scores,
backgroundColor: '#2a5298'
}]
},
options: { scales: { y: { beginAtZero: true, max: 5 } } }
});
}