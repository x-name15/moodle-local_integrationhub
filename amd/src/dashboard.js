/* global Chart */
define([], function () {
    return {
        init: function (data, strings) {
            if (typeof Chart === 'undefined') {
                return;
            }

            // ---- Charts Logic ----

            // Status Chart (Doughnut)
            const elStatus = document.getElementById('ih-chart-status');
            if (elStatus) {
                const ctxStatus = elStatus.getContext('2d');
                new Chart(ctxStatus, {
                    type: 'doughnut',
                    data: {
                        labels: [strings.success, strings.failure],
                        datasets: [{
                            data: [data.success || 0, data.fail || 0],
                            backgroundColor: ['#198754', '#dc3545'],
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'top' }
                        }
                    }
                });
            }

            // Latency Chart (Line)
            const elLatency = document.getElementById('ih-chart-latency');
            if (elLatency) {
                const ctxLatency = elLatency.getContext('2d');
                if (!data.labels || data.labels.length === 0) {
                    ctxLatency.font = "14px sans-serif";
                    ctxLatency.fillStyle = "#6c757d";
                    ctxLatency.textAlign = "center";
                    ctxLatency.fillText("No latency data available yet", elLatency.width / 2, elLatency.height / 2);
                } else {
                    new Chart(ctxLatency, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: strings.avglatency,
                                data: data.latency,
                                borderColor: '#0d6efd',
                                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                                tension: 0.3,
                                fill: true,
                                pointRadius: data.labels.length > 50 ? 0 : 3
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                x: {
                                    ticks: {
                                        autoSkip: true,
                                        maxRotation: 0
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    title: { display: true, text: 'ms' }
                                }
                            }
                        }
                    });
                }
            }

            // ---- Form Toggle Logic ----
            const form = document.getElementById('ih-service-form');
            const btnAdd = document.getElementById('ih-btn-add');
            const btnCancel = document.getElementById('ih-btn-cancel');

            if (btnAdd && form) {
                btnAdd.addEventListener('click', function () {
                    const svcId = document.getElementById('ih-serviceid');
                    const ihForm = document.getElementById('ih-form');
                    if (svcId) {
                        svcId.value = '0';
                    }
                    if (ihForm) {
                        ihForm.reset();
                    }
                    form.classList.remove('d-none');
                    btnAdd.classList.add('d-none');
                    const nameField = document.getElementById('ih-name');
                    if (nameField) {
                        nameField.focus();
                    }
                });
            }

            if (btnCancel && form) {
                btnCancel.addEventListener('click', function () {
                    form.classList.add('d-none');
                    if (btnAdd) {
                        btnAdd.classList.remove('d-none');
                    }
                });
            }
        }
    };
});
