document.addEventListener("DOMContentLoaded", function () {

    /* ==========================
       EXAM PERFORMANCE CHART
    ========================== */
    const examChartEl = document.getElementById("examChart");
    if (examChartEl) {
        new Chart(examChartEl.getContext("2d"), {
            type: "line",
            data: {
                labels: ["Jan","Feb","Mar","Apr","May","Jun"],
                datasets: [{
                    label: "Exams Taken",
                    data: [10,20,15,30,25,40],
                    borderColor: "#0d6efd",
                    backgroundColor: "rgba(13,110,253,0.2)",
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
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
    }


    /* ==========================
       OPTIONAL CHARTS (SAFE)
       Will run only if canvas exists
    ========================== */

    function initChart(id, config) {
        const el = document.getElementById(id);
        if (!el) return;
        new Chart(el.getContext("2d"), config);
    }

    // Exams per Month
    initChart("examMonthChart", {
        type: "bar",
        data: {
            labels: window.examMonthLabels || [],
            datasets: [{
                label: "Exams",
                data: window.examMonthData || []
            }]
        }
    });

    // Pass vs Fail
    initChart("passFailChart", {
        type: "doughnut",
        data: {
            labels: ["Pass", "Fail"],
            datasets: [{
                data: window.passFailData || []
            }]
        }
    });

    // Average Marks
    initChart("avgMarksChart", {
        type: "line",
        data: {
            labels: window.avgMarksLabels || [],
            datasets: [{
                label: "Avg Marks",
                data: window.avgMarksData || [],
                tension: 0.3
            }]
        }
    });

    // Most Attempted Exams
    initChart("attemptedChart", {
        type: "bar",
        data: {
            labels: window.attemptedLabels || [],
            datasets: [{
                label: "Attempts",
                data: window.attemptedData || []
            }]
        }
    });

});
