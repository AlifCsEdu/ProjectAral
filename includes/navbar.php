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
                <li><a href="menu.php" class="<?php echo $current_page == 'menu.php' ? 'active' : ''; ?>">Menu</a></li>
                <li><a href="orders.php" class="<?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">My Orders</a></li>
                <li><a href="contact.php" class="<?php echo $current_page == 'contact.php' ? 'active' : ''; ?>">Contact Us</a></li>
            </ul>
        </div>
        <a href="menu.php" class="btn btn-ghost normal-case text-xl">Aral's Food</a>
    </div>
    <div class="navbar-center hidden lg:flex">
        <ul class="menu menu-horizontal px-1">
            <li><a href="menu.php" class="<?php echo $current_page == 'menu.php' ? 'active' : ''; ?>">Menu</a></li>
            <li><a href="orders.php" class="<?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">My Orders</a></li>
            <li><a href="contact.php" class="<?php echo $current_page == 'contact.php' ? 'active' : ''; ?>">Contact Us</a></li>
        </ul>
    </div>
    <div class="navbar-end">
        <a href="cart.php" class="btn btn-ghost btn-circle">
            <div class="indicator">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span id="cart-count" class="badge badge-sm indicator-item">0</span>
            </div>
        </a>
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
