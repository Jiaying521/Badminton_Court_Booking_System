function toggleForm(id) {
    document.getElementById('adminForm').classList.remove('active');
    document.getElementById('coachForm').classList.remove('active');
    document.getElementById(id).classList.add('active');
}
