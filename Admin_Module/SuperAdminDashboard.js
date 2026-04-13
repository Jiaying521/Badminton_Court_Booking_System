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
/**
 * Filters the chart data based on the selected status from the dropdown.
 * Options: All, Completed, Cancelled, Rescheduled, Ongoing.
 */
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
    flatpickr("#inline-calendar",{
        inline: true, //direct show content not pop up
        dateFormat: "Y-m-d",
        defaultDate: "today", //defualt date
        
        locale:{
            firstDayOfWeek: 1  // Start week on Monday
        },

        onChange: function(selectedDates, dateStr){
            // Section for future transition animations and data loading logic.

            console.log("Selected date: " + dateStr); //testing date selection
        }

    });
});