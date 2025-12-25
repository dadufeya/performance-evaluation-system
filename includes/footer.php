<footer class="footer">
    <div class="footer-content">
        <p>&copy; <?= date('Y') ?> Performance Evaluation System. All rights reserved.</p>
        <nav class="footer-nav">
            <a href="/">Home</a>
            <a href="/about.php">About</a>
            <a href="/contact.php">Contact</a>
        </nav>
    </div>
</footer>

<style>
    .footer {
        background-color: #f8f9fa;
        padding: 20px 0;
        text-align: center;
        border-top: 1px solid #e9ecef;
    }

    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 15px;
    }

    .footer-nav {
        margin-top: 10px;
    }

    .footer-nav a {
        margin: 0 10px;
        color: #007bff;
        text-decoration: none;
        transition: color 0.3s;
    }

    .footer-nav a:hover {
        color: #0056b3;
    }
</style>
</body>
</html>
<script>
// Convert PHP arrays to JS for instant filtering
const allYears = <?= json_encode($years) ?>;
const allSections = <?= json_encode($sections) ?>;

function filterSelections(deptId) {
    const yearSelect = document.getElementById('year_select');
    const sectionSelect = document.getElementById('section_select');

    // Reset dropdowns
    yearSelect.innerHTML = '<option value="">-- Year --</option>';
    sectionSelect.innerHTML = '<option value="">-- Section --</option>';

    if (!deptId) return;

    // Filter and Populate Years
    // Note: If your academic_years table doesn't have department_id, remove the filter
    const filteredYears = allYears.filter(y => y.department_id == deptId || !y.department_id);
    filteredYears.forEach(y => {
        let opt = document.createElement('option');
        opt.value = y.year_id;
        opt.textContent = y.year_name;
        yearSelect.appendChild(opt);
    });

    // Filter and Populate Sections
    const filteredSections = allSections.filter(s => s.department_id == deptId);
    filteredSections.forEach(s => {
        let opt = document.createElement('option');
        opt.value = s.section_id;
        opt.textContent = 'Sec ' + s.section_number;
        sectionSelect.appendChild(opt);
    });
}
</script>
<script>
// Load full data into JS for instant filtering
const allYears = <?= json_encode($years) ?>;
const allSections = <?= json_encode($sections) ?>;

function filterSelections(deptId) {
    const yearSelect = document.getElementById('year_select');
    const sectionSelect = document.getElementById('section_select');

    // Reset dropdowns to default state
    yearSelect.innerHTML = '<option value="">-- Year --</option>';
    sectionSelect.innerHTML = '<option value="">-- Section --</option>';

    if (!deptId) return;

    // 1. Filter Years: Shows years linked to dept OR years with no dept link
    const filteredYears = allYears.filter(y => !y.department_id || y.department_id == deptId);
    filteredYears.forEach(y => {
        let opt = document.createElement('option');
        opt.value = y.year_id;
        opt.textContent = y.year_name;
        yearSelect.appendChild(opt);
    });

    // 2. Filter Sections: 
    // Logic: If the section has a department_id, filter it. 
    // If sections aren't linked to departments in your DB, it shows all of them.
    const filteredSections = allSections.filter(s => !s.department_id || s.department_id == deptId);
    
    // If the filter results in nothing, show all sections as a fallback
    const finalSections = filteredSections.length > 0 ? filteredSections : allSections;

    finalSections.forEach(s => {
        let opt = document.createElement('option');
        opt.value = s.section_id;
        opt.textContent = 'Sec ' + (s.section_number || s.section_name || s.section_id);
        sectionSelect.appendChild(opt);
    });
}
</script>