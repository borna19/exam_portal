// Chart.js - Exam Performance
const ctx = document.getElementById('examChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun'], // Replace with dynamic months if needed
        datasets: [{
            label: 'Exams Taken',
            data: [10,20,15,30,25,40],
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13,110,253,0.2)',
            borderWidth: 2,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
