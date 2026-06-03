// 1. BOOKING STATISTICS FILTER
function filterStats() {
    const filterValue = document.getElementById("statusFilter").value;
    if (!appointmentChart) return;
    appointmentChart.data.datasets.forEach((dataset) => {
        dataset.hidden = (filterValue !== "All") && (dataset.label !== filterValue);
    });
    appointmentChart.update();
}

// 2. CHART.JS INITIALIZATION
let appointmentChart;

document.addEventListener('DOMContentLoaded', function() {
    const chartCanvas = document.getElementById('myChart');
    if (!chartCanvas) return;

    const ctx = chartCanvas.getContext('2d');
    const dataSet = typeof chartData !== 'undefined' ? chartData : { pending: [], confirmed: [], completed: [], cancelled: [] };

    appointmentChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
            datasets: [
                { label: 'Pending',   data: dataSet.pending,   backgroundColor: '#ffc107' },
                { label: 'Confirmed', data: dataSet.confirmed, backgroundColor: '#007bff' },
                { label: 'Completed', data: dataSet.completed, backgroundColor: '#28a745' },
                { label: 'Cancelled', data: dataSet.cancelled, backgroundColor: '#dc3545' }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    suggestedMax: 10,
                    ticks: {
                        stepSize: 2,
                        precision: 0
                    }
                }
            },
            plugins: { legend: { position: 'bottom' } }
        }
    });
});

// 3. MONTHLY REVENUE CHART
document.addEventListener('DOMContentLoaded', function() {
    const revenueCanvas = document.getElementById('revenueChart');
    if (!revenueCanvas) return;

    const extra = typeof extraChartData !== 'undefined' ? extraChartData : { revenue: [] };

    new Chart(revenueCanvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
            datasets: [{
                label: 'Revenue (RM)',
                data: extra.revenue,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245,158,11,0.08)',
                borderWidth: 2,
                pointBackgroundColor: '#f59e0b',
                pointRadius: 4,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: val => 'RM ' + val }
                }
            }
        }
    });
});

// 4. COURT REVENUE BREAKDOWN (DOUGHNUT)
document.addEventListener('DOMContentLoaded', function() {
    const courtCanvas = document.getElementById('courtRevenueChart');
    if (!courtCanvas) return;

    const extra = typeof extraChartData !== 'undefined' ? extraChartData : { courtLabels: [], courtRevenues: [] };

    if (extra.courtLabels.length === 0) {
        courtCanvas.parentElement.innerHTML = '<p style="text-align:center; color:#999; padding:40px 0; font-size:13px;">No revenue data yet.</p>';
        return;
    }

    new Chart(courtCanvas.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: extra.courtLabels,
            datasets: [{
                data: extra.courtRevenues,
                backgroundColor: ['#f59e0b', '#007bff', '#28a745', '#dc3545', '#6366f1', '#f97316'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': RM ' + context.parsed.toFixed(2);
                        }
                    }
                }
            }
        }
    });
});

// 5. PEAK BOOKING HOURS CHART
document.addEventListener('DOMContentLoaded', function() {
    const peakCanvas = document.getElementById('peakChart');
    if (!peakCanvas) return;

    const hours  = typeof peakHours  !== 'undefined' ? peakHours  : [];
    const labels = typeof peakLabels !== 'undefined' ? peakLabels : [];
    const open   = typeof peakOpen   !== 'undefined' ? peakOpen   : 8;

    // Peak starts at 3pm (15:00). Off-peak = before 3pm, peak = 3pm onward
    const colors = labels.map((_, i) => (open + i) >= 15 ? '#f59e0b' : '#93c5fd');

    // Shade peak zone background
    const peakBandPlugin = {
        id: 'peakBand',
        beforeDraw(chart) {
            const { ctx, chartArea, scales } = chart;
            if (!chartArea || !scales.x) return;
            const peakIdx = 15 - open;
            if (peakIdx < 0 || peakIdx >= labels.length) return;
            const xStart = scales.x.getPixelForValue(peakIdx) - (scales.x.getPixelForValue(1) - scales.x.getPixelForValue(0)) / 2;
            const xEnd   = chartArea.right;
            ctx.save();
            ctx.fillStyle = 'rgba(245,158,11,0.07)';
            ctx.fillRect(xStart, chartArea.top, xEnd - xStart, chartArea.bottom - chartArea.top);
            ctx.restore();
        }
    };

    new Chart(peakCanvas.getContext('2d'), {
        type: 'bar',
        plugins: [peakBandPlugin],
        data: {
            labels: labels,
            datasets: [{
                label: 'Bookings',
                data: hours,
                backgroundColor: colors,
                borderRadius: 5,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    labels: {
                        generateLabels: () => [
                            { text: 'Off-Peak', fillStyle: '#93c5fd', strokeStyle: '#93c5fd', lineWidth: 0 },
                            { text: 'Peak (3PM+)', fillStyle: '#f59e0b', strokeStyle: '#f59e0b', lineWidth: 0 }
                        ],
                        boxWidth: 12,
                        font: { size: 12 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: ctx => ctx.parsed.y + ' booking' + (ctx.parsed.y !== 1 ? 's' : '')
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, precision: 0 }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
});

// 6. SESSION TYPE DISTRIBUTION
document.addEventListener('DOMContentLoaded', function() {
    const sessionCanvas = document.getElementById('sessionChart');
    if (!sessionCanvas) return;

    const labels = (typeof sessionLabels !== 'undefined' ? sessionLabels : [])
        .map(l => (l && l.trim() !== '') ? l : 'Unknown Booking');
    const counts = typeof sessionCounts !== 'undefined' ? sessionCounts : [];

    if (labels.length === 0) {
        sessionCanvas.parentElement.innerHTML = '<div style="height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#9ca3af;font-size:13px;font-style:italic;gap:8px;"><i class="fas fa-inbox" style="font-size:26px;color:#e5e7eb;"></i>No data yet</div>';
        return;
    }

    new Chart(sessionCanvas.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: counts,
                backgroundColor: ['#f59e0b', '#007bff', '#28a745', '#dc3545', '#94a3b8'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 12 } } },
                tooltip: {
                    callbacks: {
                        label: ctx => ctx.label + ': ' + ctx.parsed + ' bookings'
                    }
                }
            }
        }
    });
});

// 6. FLATPICKR CALENDAR
document.addEventListener('DOMContentLoaded', function() {
    if (!document.getElementById('inline-calendar')) return;

    function fetchAppointments(dateStr) {
        const listDiv = document.getElementById('mini-appt-list');
        const viewAllBtn = document.getElementById('view-all-btn');

        if (!dateStr) dateStr = new Date().toISOString().split('T')[0];

        listDiv.innerHTML = '<p style="text-align:center; font-size:13px; color:#666; padding:10px;">Searching...</p>';

        fetch(`Dashboard.php?ajax_fetch=${dateStr}`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                listDiv.innerHTML = '';

                if (data.error) {
                    listDiv.innerHTML = `<p style="text-align:center; color:red; padding:10px;">Error: ${data.error}</p>`;
                    return;
                }

                if (Array.isArray(data) && data.length > 0) {
                    data.forEach((app, index) => {
                        const cardClass = (index % 2 === 0) ? 'appt-card' : 'appt-card alt';
                        const coachLine = app.coach_name && app.coach_name !== 'No coach' ? ` | Coach: ${app.coach_name}` : '';
                        const link = `../Bookings_Management/ManageBookings.php?highlight=${app.booking_id}`;
                        listDiv.innerHTML += `
                            <a href="${link}" class="${cardClass}" style="text-decoration:none; display:flex; color:inherit;">
                                <div class="appt-content" style="flex:1;">
                                    <h4>${app.court_name || 'Court'} - ${app.player_name}</h4>
                                    <div class="appt-details">
                                        <i class="far fa-calendar-check"></i>
                                        ${dateStr}, ${app.booking_time} - ${app.end_time}${coachLine}
                                    </div>
                                </div>
                                <div class="appt-arrow">
                                    <i class="fas fa-chevron-right" style="color:#ccc; align-self:center;"></i>
                                </div>
                            </a>
                        `;
                    });
                    viewAllBtn.href = `../Bookings_Management/ManageBookings.php?date=${dateStr}`;
                    viewAllBtn.style.display = 'block';
                } else {
                    listDiv.innerHTML = '<div class="appt-empty"><i class="fas fa-inbox"></i>No booking found</div>';
                    viewAllBtn.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                listDiv.innerHTML = '<p style="text-align:center; color:red; padding:10px;">Failed to load data.</p>';
            });
    }

    const dates = typeof bookingDates !== 'undefined' ? bookingDates : [];

    const fpInstance = flatpickr("#inline-calendar", {
        inline: true,
        dateFormat: "Y-m-d",
        defaultDate: "today",
        locale: { firstDayOfWeek: 1 },
        onReady: function(selectedDates, dateStr, instance) {
            const initialDate = instance.formatDate(instance.now, "Y-m-d");
            fetchAppointments(dateStr || initialDate);
        },
        onChange: function(selectedDates, dateStr) {
            fetchAppointments(dateStr);
        },
        onDayCreate: function(dObj, dStr, fp, dayElem) {
            const d = fp.formatDate(dayElem.dateObj, "Y-m-d");
            if (dates.includes(d)) {
                const dot = document.createElement("span");
                dot.className = "booking-dot";
                dayElem.appendChild(dot);
            }
        }
    });

    // Redraw calendar when window resizes so it fits the column correctly
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            fpInstance.redraw();
        }, 150);
    });
});
