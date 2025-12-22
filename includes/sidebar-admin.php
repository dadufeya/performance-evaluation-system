<aside class="sidebar">
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="sidebar-item">
            <i class="icon-dashboard"></i> Dashboard
        </a>
        <a href="manage-students.php" class="sidebar-item">
            <i class="icon-students"></i> Students
        </a>
        <a href="manage-teachers.php" class="sidebar-item">
            <i class="icon-teachers"></i> Teachers
        </a>
        <a href="manage-departments.php" class="sidebar-item">
            <i class="icon-departments"></i> Departments
        </a>
        <a href="manage-years.php" class="sidebar-item">
            <i class="icon-years"></i> Years
        </a>
        <a href="manage-sections.php" class="sidebar-item">
            <i class="icon-sections"></i> Sections
        </a>
        <a href="manage-courses.php" class="sidebar-item">
            <i class="icon-courses"></i> Courses
        </a>
        <a href="create-questionnaire.php" class="sidebar-item">
            <i class="icon-questionnaire"></i> Questionnaire
        </a>
        <a href="view-evaluations.php" class="sidebar-item">
            <i class="icon-evaluations"></i> Evaluations
        </a>
        <a href="complaints.php" class="sidebar-item">
            <i class="icon-complaints"></i> Complaints
        </a>
    </nav>
</aside>

<style>
    .sidebar {
        width: 250px;
        background: linear-gradient(180deg, #007bff, #0056b3);
        color: #fff;
        height: calc(100vh - 60px); /* Adjusted to connect under the header */
        position: fixed;
        top: 60px; /* Starts below the header */
        left: 0;
        display: flex;
        flex-direction: column;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);
        z-index: 999; /* Ensures it stays below the header */
    }

    .sidebar-nav {
        display: flex;
        flex-direction: column;
        padding: 10px 0;
    }

    .sidebar-item {
        padding: 15px 20px;
        color: #fff;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1rem;
        font-weight: 500;
        transition: background 0.3s, transform 0.2s;
    }

    .sidebar-item:hover {
        background-color: rgba(255, 255, 255, 0.2);
        transform: scale(1.05);
    }

    .sidebar-item i {
        font-size: 1.2rem;
    }

    .icon-dashboard::before { content: '\1F4CA'; }
    .icon-students::before { content: '\1F393'; }
    .icon-teachers::before { content: '\1F464'; }
    .icon-departments::before { content: '\1F3E2'; }
    .icon-years::before { content: '\1F4C5'; }
    .icon-sections::before { content: '\1F4DA'; }
    .icon-courses::before { content: '\1F4D6'; }
    .icon-questionnaire::before { content: '\270F'; }
    .icon-evaluations::before { content: '\1F4DD'; }
    .icon-complaints::before { content: '\1F4E2'; }
</style>