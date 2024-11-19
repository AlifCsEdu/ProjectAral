<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="navbar glass-nav sticky top-0 z-50">
    <div class="navbar-start">
        <div class="dropdown">
            <label tabindex="0" class="btn btn-ghost lg:hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" />
                </svg>
            </label>
            <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52">
                <li><a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="orders.php" class="<?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">Orders</a></li>
                <li><a href="menu_items.php" class="<?php echo $current_page == 'menu_items.php' ? 'active' : ''; ?>">Menu Items</a></li>
                <li><a href="categories.php" class="<?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">Categories</a></li>
                <li><a href="users.php" class="<?php echo $current_page == 'users.php' ? 'active' : ''; ?>">Users</a></li>
                <li><a href="contact_messages.php" class="<?php echo $current_page == 'contact_messages.php' ? 'active' : ''; ?>">Messages</a></li>
            </ul>
        </div>
        <a href="dashboard.php" class="btn btn-ghost normal-case text-xl">Admin Dashboard</a>
    </div>
    <div class="navbar-center hidden lg:flex">
        <ul class="menu menu-horizontal px-1">
            <li><a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
            <li><a href="orders.php" class="<?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">Orders</a></li>
            <li><a href="menu_items.php" class="<?php echo $current_page == 'menu_items.php' ? 'active' : ''; ?>">Menu Items</a></li>
            <li><a href="categories.php" class="<?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">Categories</a></li>
            <li><a href="users.php" class="<?php echo $current_page == 'users.php' ? 'active' : ''; ?>">Users</a></li>
            <li><a href="contact_messages.php" class="<?php echo $current_page == 'contact_messages.php' ? 'active' : ''; ?>">Messages</a></li>
        </ul>
    </div>
    <div class="navbar-end">
        <div class="dropdown dropdown-end">
            <label tabindex="0" class="btn btn-ghost btn-circle avatar">
                <div class="w-10 rounded-full">
                    <img src="../assets/images/default-avatar.png" alt="Profile" />
                </div>
            </label>
            <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52">
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</div>
