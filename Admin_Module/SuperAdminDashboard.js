// 1. SELECT DOM ELEMENTS
const menuToggle = document.getElementById('menu-toggle');
const navMenu = document.getElementById('nav-menu');
const logoutBtn = document.getElementById("logout-btn");
const welcomeText = document.getElementById('welcome-text');

// 2. MOBILE NAVIGATION LOGIC
// Toggle sidebar menu for mobile view
menuToggle.addEventListener('click', (e) => {
    e.stopPropagation();
    navMenu.classList.toggle('active');
});

// Close menu when clicking outside the navigation area
document.addEventListener('click', (event) => {
    const isClickInsideMenu = navMenu.contains(event.target);
    const isClickOnButton = menuToggle.contains(event.target);

    if (navMenu.classList.contains('active') && !isClickInsideMenu && !isClickOnButton) {
        navMenu.classList.remove('active');
    }
});

// 3. USER LOGOUT SYSTEM
logoutBtn.addEventListener("click", function() {
    const confirmLogout = confirm("Are you sure you want to log out of the system?");

    if (confirmLogout) {
        // Clear local browser data
        localStorage.removeItem("loggedInUser");
        
        // Redirect to the current page with a logout action parameter
        window.location.href = "SuperAdminDashboard.php?action=logout"; 
    }
});

// 4. APPOINTMENT STATISTICS FILTER LOGIC
function filterStats() {
    const filterValue = document.getElementById("statusFilter").value;

    if (!appointmentChart) return;

    // Iterate through datasets to show/hide based on selection
    appointmentChart.data.datasets.forEach((dataset) => {
        if (filterValue === "All") {
            dataset.hidden = false; // Show all data
        } else {
            // Hide datasets that do not match the selected label
            dataset.hidden = (dataset.label !== filterValue);
        }
    });
    
    appointmentChart.update(); // Re-render the chart with filtered data
}

// 5. CHART.JS INITIALIZATION
let appointmentChart;

document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('myChart').getContext('2d');
    
    // Safety check: ensure chartData is provided by PHP
    const dataSet = typeof chartData !== 'undefined' ? chartData : { rescheduled: [], completed: [], cancelled: [], ongoing: [] };

    appointmentChart = new Chart(ctx, {
        type: 'bar', 
        data: {
            labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
            datasets: [
                {
                    label: 'Rescheduled',
                    data: dataSet.rescheduled,
                    backgroundColor: '#ffc107' // Amber
                },
                {
                    label: 'Completed',
                    data: dataSet.completed,
                    backgroundColor: '#28a745' // Green
                },
                {
                    label: 'Cancelled',
                    data: dataSet.cancelled,
                    backgroundColor: '#dc3545' // Red
                },
                {
                    label: 'Ongoing',
                    data: dataSet.ongoing,
                    backgroundColor: '#007bff' // Blue
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { 
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1 // Ensures Y-axis uses whole numbers (people count)
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});

//6. Flatpickr Calendar Initialization
document.addEventListener('DOMContentLoaded', function() {
    
    function fetchAppointments(dateStr) {
        const listDiv = document.getElementById('mini-appt-list');
        const viewAllBtn = document.getElementById('view-all-btn');

        // If dateStr is empty (sometimes happens on initial load), use default
        if (!dateStr) {
            dateStr = new Date().toISOString().split('T')[0];
        }

        listDiv.innerHTML = '<p style="text-align:center; font-size:13px; color:#666; padding:10px;">Searching...</p>';

        fetch(`SuperAdminDashboard.php?ajax_fetch=${dateStr}`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                // DEBUG: Press F12 in browser to see this output
                console.log("Fetching for date:", dateStr, "Data:", data);

                listDiv.innerHTML = '';

                // Handle server-side SQL errors
                if (data.error) {
                    listDiv.innerHTML = `<p style="text-align:center; color:red; padding:10px;">SQL Error: ${data.error}</p>`;
                    return;
                }

                if (Array.isArray(data) && data.length > 0) {
                    data.forEach((app, index) => {
                        const cardClass = (index % 2 === 0) ? 'appt-card' : 'appt-card alt';

                        listDiv.innerHTML += `
                            <div class="${cardClass}">
                                <div class="appt-content">
                                    <h4>Court Booking - ${app.player_name}</h4>
                                    <div class="appt-details">
                                        <i class="far fa-calendar-check"></i> 
                                        ${dateStr}, ${app.appointment_time}
                                    </div>
                                </div>
                                <div class="appt-arrow">
                                    <i class="fas fa-chevron-right" style="color: #ccc;"></i>
                                </div>
                            </div>
                        `;
                    });
                    viewAllBtn.style.display = 'block';
                } else {
                    listDiv.innerHTML = '<p style="text-align:center; color:#999; padding:20px; font-size:13px;">No records found for this date.</p>';
                    viewAllBtn.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                listDiv.innerHTML = '<p style="text-align:center; color:red; padding:10px;">Failed to load data. Check Console (F12).</p>';
            });
    }

    // Initialize Flatpickr
    flatpickr("#inline-calendar", {
        inline: true, 
        dateFormat: "Y-m-d",
        defaultDate: "today", 
        
        locale: {
            firstDayOfWeek: 1  
        },

        // Trigger fetch when the calendar is ready
        onReady: function(selectedDates, dateStr, instance) {
            // Use instance.currentYear/currentMonth to ensure correct date on load
            const initialDate = instance.formatDate(instance.now, "Y-m-d");
            fetchAppointments(dateStr || initialDate);
        },

        // Trigger fetch when a new date is selected
        onChange: function(selectedDates, dateStr) {
            fetchAppointments(dateStr);
        }
    }); 
});