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
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
            plugins: { legend: { position: 'bottom' } }
        }
    });
});

// 3. FLATPICKR CALENDAR
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
                    listDiv.innerHTML = '<p style="text-align:center; color:#999; padding:20px; font-size:13px;">No records found for this date.</p>';
                    viewAllBtn.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                listDiv.innerHTML = '<p style="text-align:center; color:red; padding:10px;">Failed to load data.</p>';
            });
    }

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
