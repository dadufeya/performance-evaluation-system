/**
 * main.js - Centralized application logic
 */

document.addEventListener('DOMContentLoaded', () => {
    // === Sidebar Toggle ===
    // If you add a hamburger menu later, use this.
    // const toggleBtn = document.querySelector('.sidebar-toggle');
    // if(toggleBtn) {
    //     toggleBtn.addEventListener('click', () => {
    //         document.querySelector('.sidebar').classList.toggle('active');
    //     });
    // }

    // === Global Form Handling ===
    // (None for now)
});

// === Teacher Management Hierarchy Logic ===
// Used in admin/manage-teachers.php

function initTeacherHierarchy(yearsData, coursesData) {
    const deptSelect = document.getElementById('d_main');
    const courseSelect = document.getElementById('c_main');
    const yearSelect = document.getElementById('y_main');
    const sectionSelect = document.getElementById('s_main');

    if(!deptSelect) return; // Not on the page

    deptSelect.addEventListener('change', function() {
        const deptId = this.value;

        // Reset and Disable downstream
        resetSelect(courseSelect, '-- Select Course --');
        resetSelect(yearSelect, '-- Select Year --');
        resetSelect(sectionSelect, '-- Select Year First --');
        yearSelect.disabled = true;
        sectionSelect.disabled = true;

        if (deptId) {
            // Enable and Populate Courses
            const relevantCourses = coursesData.filter(c => c.department_id == deptId);
            populateSelect(courseSelect, relevantCourses, 'course_id', 'course_name');
            courseSelect.disabled = false;

            // Enable and Populate Years (Associated with Dept)
            // Note: If years are not strictly linked to dept in DB, show all.
            // But based on user code, they effectively filter everything.
            // Assuming academic_years might not have department_id, we might check logic.
            // The original code filtered yearsMaster by department_id.
            const relevantYears = yearsData.filter(y => y.department_id == deptId);
            
            if(relevantYears.length > 0) {
                 populateSelect(yearSelect, relevantYears, 'year_id', 'year_name');
                 yearSelect.disabled = false;
            } else {
                // Fallback: if years table has no dep_id, maybe show all?
                // For now, stick to original logic: must match dept.
                console.warn('No years found for this department');
            }
        } else {
            courseSelect.disabled = true;
        }
    });

    yearSelect.addEventListener('change', function() {
        const yearId = this.value;
        resetSelect(sectionSelect, '-- Select Section --');
        
        if(yearId) {
            // Fetch sections via AJAX as per original design
            fetchSections(yearId, sectionSelect);
        } else {
            sectionSelect.disabled = true;
        }
    });
}

// Helper: Reset Select Box
function resetSelect(el, defaultText) {
    el.innerHTML = `<option value="">${defaultText}</option>`;
}

// Helper: Populate Select Box
function populateSelect(el, data, valKey, textKey) {
    data.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item[valKey];
        opt.textContent = item[textKey];
        el.appendChild(opt);
    });
}

// Helper: AJAX Fetch Sections
function fetchSections(yearId, sectionEl) {
    // Ensure we are fetching from the correct path relative to the script execution
    // Since this is included in admin/manage-teachers.php, 'fetch_sections.php' works.
    fetch('fetch_sections.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'year_id=' + yearId
    })
    .then(response => response.text())
    .then(html => {
        sectionEl.innerHTML = html;
        sectionEl.disabled = false;
    })
    .catch(err => console.error('Error fetching sections:', err));
}

// === Search Registry ===
function searchRegistry() {
    let input = document.getElementById('registrySearch');
    if(!input) return;
    let filter = input.value.toLowerCase();
    let rows = document.querySelectorAll('#teacherTable tbody tr');
    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(filter) ? "" : "none";
    });
}

// === Modal Logic ===
function openEdit(t) {
    document.getElementById('e_tid').value = t.teacher_id;
    document.getElementById('e_name').value = t.full_name;
    document.getElementById('e_email').value = t.email;
    document.getElementById('e_phone').value = t.phone;
    document.getElementById('e_dept').value = t.department_id;
    document.getElementById('e_course').value = t.course_info;
    document.getElementById('e_year').value = t.year;
    document.getElementById('e_sec').value = t.section;
    document.getElementById('editModal').style.display = 'block';
}

function closeEdit() { 
    document.getElementById('editModal').style.display = 'none'; 
}

function preparePrint(d) {
    document.getElementById('p_id').innerText = d.teacher_id;
    document.getElementById('p_name').innerText = d.full_name;
    document.getElementById('p_pass').innerText = d.temp_pass;
    window.print();
}
