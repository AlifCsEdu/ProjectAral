<nav class="bg-base-100 shadow-lg">
    <div class="navbar container mx-auto">
        <div class="navbar-start">
            <div class="dropdown">
                <label tabindex="0" class="btn btn-ghost lg:hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" />
                    </svg>
                </label>
                <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52">
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="../user/menu.php">Menu</a></li>
                    <li><a href="../user/cart.php">Cart</a></li>
                    <li><a href="profile.php" class="active">Profile</a></li>
                    <li><a href="../user/orders.php">My Orders</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </div>
            <a href="../index.php" class="btn btn-ghost normal-case text-xl">Aral's Food</a>
        </div>
        <div class="navbar-center hidden lg:flex">
            <ul class="menu menu-horizontal px-1">
                <li><a href="../user/index.php">Home</a></li>
                <li><a href="../user/menu.php">Menu</a></li>
                <li><a href="../user/cart.php">Cart</a></li>
                <li><a href="profile.php" class="active">Profile</a></li>
                <li><a href="../user/orders.php">My Orders</a></li>
            </ul>
        </div>
        <div class="navbar-end">
            <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-ghost btn-circle avatar">
                    <div class="w-10 rounded-full">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name'] ?? 'User'); ?>&background=random" />
                    </div>
                </label>
                <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52">
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
