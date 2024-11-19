<?php
$current_page = basename($_SERVER['PHP_SELF']);

function isActive($page) {
    global $current_page;
    return $current_page === $page ? 'active' : '';
}
?>
<div class="drawer-side">
    <label for="my-drawer-2" class="drawer-overlay"></label>
    <aside class="bg-base-200 w-[var(--sidebar-width)] transition-all duration-300" id="sidebar">
        <div class="flex flex-col h-full">
            <!-- Header with Toggle -->
            <div class="p-4 flex items-center justify-between border-b border-base-300">
                <div class="flex items-center gap-4 sidebar-expanded">
                    <div class="avatar">
                        <div class="w-10 rounded-full">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name'] ?? 'Admin'); ?>&background=random" />
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-bold text-sm"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                        <span class="text-xs opacity-70">Administrator</span>
                    </div>
                </div>
                <button class="btn btn-square btn-ghost btn-sm" id="sidebar-toggle">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 p-4">
                <ul class="menu menu-vertical gap-2">
                    <li>
                        <a href="../index.php" class="flex items-center gap-3 h-11 hover:bg-base-300 rounded-lg" title="Homepage">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span class="sidebar-expanded">Homepage</span>
                        </a>
                    </li>
                    <li>
                        <a href="dashboard.php" class="flex items-center gap-3 h-11 hover:bg-base-300 rounded-lg <?php echo isActive('dashboard.php'); ?>" title="Dashboard">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            <span class="sidebar-expanded">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="orders.php" class="flex items-center gap-3 h-11 hover:bg-base-300 rounded-lg <?php echo isActive('orders.php'); ?>" title="Orders">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <span class="sidebar-expanded">Orders</span>
                        </a>
                    </li>
                    <li>
                        <a href="menu.php" class="flex items-center gap-3 h-11 hover:bg-base-300 rounded-lg <?php echo isActive('menu.php'); ?>" title="Menu Items">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span class="sidebar-expanded">Menu Items</span>
                        </a>
                    </li>
                    <li>
                        <a href="categories.php" class="flex items-center gap-3 h-11 hover:bg-base-300 rounded-lg <?php echo isActive('categories.php'); ?>" title="Categories">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                            <span class="sidebar-expanded">Categories</span>
                        </a>
                    </li>
                    <li>
                        <a href="users.php" class="flex items-center gap-3 h-11 hover:bg-base-300 rounded-lg <?php echo isActive('users.php'); ?>" title="Users">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <span class="sidebar-expanded">Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="reports.php" class="flex items-center gap-3 h-11 hover:bg-base-300 rounded-lg <?php echo isActive('reports.php'); ?>" title="Reports">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="sidebar-expanded">Reports</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Footer -->
            <div class="p-4 border-t border-base-300">
                <a href="../logout.php" class="flex items-center gap-3 h-11 hover:bg-base-300 rounded-lg text-error" title="Logout">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span class="sidebar-expanded">Logout</span>
                </a>
            </div>
        </div>
    </aside>
</div>

<style>
:root {
    --sidebar-width: 280px;
}

#sidebar {
    min-height: 100vh;
    height: 100%;
}

#sidebar.compact {
    --sidebar-width: 80px;
}

#sidebar.compact .sidebar-expanded {
    display: none;
}

#sidebar.compact #sidebar-toggle svg {
    transform: rotate(180deg);
}

.active {
    @apply bg-primary text-primary-content;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebar-toggle');
    
    // Load saved state
    const isCompact = localStorage.getItem('sidebarCompact') === 'true';
    if (isCompact) {
        sidebar.classList.add('compact');
    }
    
    toggle.addEventListener('click', function() {
        sidebar.classList.toggle('compact');
        localStorage.setItem('sidebarCompact', sidebar.classList.contains('compact'));
    });
    
    // Add keyboard shortcut
    document.addEventListener('keydown', function(e) {
        // Alt + S to toggle sidebar
        if (e.altKey && e.key.toLowerCase() === 's') {
            e.preventDefault();
            toggle.click();
        }
    });
});
</script>
