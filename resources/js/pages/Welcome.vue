<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Attendify - Smart Attendance Management">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <!-- Header Navigation -->
        <header class="w-full px-6 py-4 lg:px-8">
            <nav class="flex items-center justify-between max-w-7xl mx-auto">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Attendify</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Smart Attendance Management</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="inline-block px-4 py-2 text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition-colors"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="register()"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
                        >
                            Get Started
                        </Link>
                    </template>
                </div>
            </nav>
        </header>

        <!-- Hero Section -->
        <section class="px-6 py-20 lg:px-8">
            <div class="max-w-7xl mx-auto text-center">
                <div class="mb-8">
                    <div class="inline-flex p-4 bg-white/20 backdrop-blur-sm rounded-2xl mb-6">
                        <svg class="w-16 h-16 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <h1 class="text-5xl font-bold text-gray-900 dark:text-white mb-4">
                        Attendify
                    </h1>
                    <p class="text-xl text-gray-600 dark:text-gray-300 mb-4">
                        Smart Attendance Management System
                    </p>
                    <p class="text-lg text-gray-500 dark:text-gray-400 max-w-3xl mx-auto">
                        A comprehensive digital attendance solution designed to simplify tracking student presence 
                        in classes, providing flexible attendance-taking methods and organized reporting for educators.
                    </p>
                </div>
            </div>
        </section>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-6 py-12 lg:px-8 space-y-20">
            
            <!-- Key Features Overview -->
            <section>
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Key Features</h2>
                    <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                        Comprehensive attendance management with flexible tracking methods and detailed reporting
                    </p>
                </div>
                
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <!-- Mobile App -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
                        <div class="bg-blue-100 dark:bg-blue-900/30 p-3 rounded-lg w-fit mb-4">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Mobile App</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">QR code scanning and camera-based attendance</p>
                    </div>

                    <!-- Web App -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
                        <div class="bg-green-100 dark:bg-green-900/30 p-3 rounded-lg w-fit mb-4">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Web Interface</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Manual input and PC camera scanning</p>
                    </div>

                    <!-- Time Management -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
                        <div class="bg-purple-100 dark:bg-purple-900/30 p-3 rounded-lg w-fit mb-4">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Time Windows</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Set start/end times with late detection</p>
                    </div>

                    <!-- Reporting -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
                        <div class="bg-orange-100 dark:bg-orange-900/30 p-3 rounded-lg w-fit mb-4">
                            <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Excel Reports</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Export attendance data with auto dates</p>
                    </div>
                </div>
            </section>

            <!-- Student Status Categories -->
            <section>
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Student Status Management</h2>
                    <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                        Track different attendance states with approval workflows for excused absences
                    </p>
                </div>
                
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Present -->
                    <div class="flex items-center bg-white dark:bg-gray-800 rounded-lg p-4 shadow-md border border-gray-100 dark:border-gray-700">
                        <div class="p-2 rounded-full bg-green-500 mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Present</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Student attended class</div>
                        </div>
                    </div>

                    <!-- Absent -->
                    <div class="flex items-center bg-white dark:bg-gray-800 rounded-lg p-4 shadow-md border border-gray-100 dark:border-gray-700">
                        <div class="p-2 rounded-full bg-red-500 mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Absent</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Student did not attend</div>
                        </div>
                    </div>

                    <!-- Excused -->
                    <div class="flex items-center bg-white dark:bg-gray-800 rounded-lg p-4 shadow-md border border-gray-100 dark:border-gray-700">
                        <div class="p-2 rounded-full bg-yellow-500 mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Excused</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Justified absence with reason</div>
                        </div>
                    </div>

                    <!-- Dropped -->
                    <div class="flex items-center bg-white dark:bg-gray-800 rounded-lg p-4 shadow-md border border-gray-100 dark:border-gray-700">
                        <div class="p-2 rounded-full bg-gray-500 mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Dropped</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">No longer in class</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Data Management Features -->
            <section>
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Data Management</h2>
                    <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                        Comprehensive data handling with import/export capabilities and cloud synchronization
                    </p>
                </div>
                
                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Export Features -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
                        <div class="bg-blue-100 dark:bg-blue-900/30 p-3 rounded-lg w-fit mb-4">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Export & Reporting</h3>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <li class="flex items-center">
                                <span class="w-1.5 h-1.5 bg-blue-400 rounded-full mr-3"></span>
                                Export to Excel format
                            </li>
                            <li class="flex items-center">
                                <span class="w-1.5 h-1.5 bg-blue-400 rounded-full mr-3"></span>
                                Sequential date generation
                            </li>
                            <li class="flex items-center">
                                <span class="w-1.5 h-1.5 bg-blue-400 rounded-full mr-3"></span>
                                Customizable report periods
                            </li>
                        </ul>
                    </div>

                    <!-- Import Features -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
                        <div class="bg-green-100 dark:bg-green-900/30 p-3 rounded-lg w-fit mb-4">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Bulk Import</h3>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <li class="flex items-center">
                                <span class="w-1.5 h-1.5 bg-green-400 rounded-full mr-3"></span>
                                PDF/CSV file upload
                            </li>
                            <li class="flex items-center">
                                <span class="w-1.5 h-1.5 bg-green-400 rounded-full mr-3"></span>
                                Auto-create class records
                            </li>
                            <li class="flex items-center">
                                <span class="w-1.5 h-1.5 bg-green-400 rounded-full mr-3"></span>
                                Organized folder structure
                            </li>
                        </ul>
                    </div>

                    <!-- Sync Features -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
                        <div class="bg-purple-100 dark:bg-purple-900/30 p-3 rounded-lg w-fit mb-4">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Cloud Sync</h3>
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <li class="flex items-center">
                                <span class="w-1.5 h-1.5 bg-purple-400 rounded-full mr-3"></span>
                                Real-time synchronization
                            </li>
                            <li class="flex items-center">
                                <span class="w-1.5 h-1.5 bg-purple-400 rounded-full mr-3"></span>
                                Multi-device access
                            </li>
                            <li class="flex items-center">
                                <span class="w-1.5 h-1.5 bg-purple-400 rounded-full mr-3"></span>
                                Permanent record storage
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- User Roles -->
            <section>
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">User Roles</h2>
                    <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                        Tailored interfaces and permissions for different user types
                    </p>
                </div>
                
                <div class="grid md:grid-cols-2 gap-8">
                    <!-- Teacher Role -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-8 shadow-lg border border-gray-100 dark:border-gray-700">
                        <div class="bg-blue-100 dark:bg-blue-900/30 p-4 rounded-lg w-fit mb-6">
                            <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Teacher</h3>
                        <ul class="space-y-3 text-gray-600 dark:text-gray-400">
                            <li class="flex items-center">
                                <span class="w-2 h-2 bg-blue-400 rounded-full mr-3"></span>
                                Create and manage classes
                            </li>
                            <li class="flex items-center">
                                <span class="w-2 h-2 bg-blue-400 rounded-full mr-3"></span>
                                Track student attendance
                            </li>
                            <li class="flex items-center">
                                <span class="w-2 h-2 bg-blue-400 rounded-full mr-3"></span>
                                Validate excuse submissions
                            </li>
                            <li class="flex items-center">
                                <span class="w-2 h-2 bg-blue-400 rounded-full mr-3"></span>
                                Generate reports
                            </li>
                            <li class="flex items-center">
                                <span class="w-2 h-2 bg-blue-400 rounded-full mr-3"></span>
                                Set attendance windows
                            </li>
                        </ul>
                    </div>

                    <!-- Student Role -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-8 shadow-lg border border-gray-100 dark:border-gray-700">
                        <div class="bg-green-100 dark:bg-green-900/30 p-4 rounded-lg w-fit mb-6">
                            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Student</h3>
                        <ul class="space-y-3 text-gray-600 dark:text-gray-400">
                            <li class="flex items-center">
                                <span class="w-2 h-2 bg-green-400 rounded-full mr-3"></span>
                                Check attendance status
                            </li>
                            <li class="flex items-center">
                                <span class="w-2 h-2 bg-green-400 rounded-full mr-3"></span>
                                Submit excuse reasons
                            </li>
                            <li class="flex items-center">
                                <span class="w-2 h-2 bg-green-400 rounded-full mr-3"></span>
                                View attendance history
                            </li>
                            <li class="flex items-center">
                                <span class="w-2 h-2 bg-green-400 rounded-full mr-3"></span>
                                Receive notifications
                            </li>
                            <li class="flex items-center">
                                <span class="w-2 h-2 bg-green-400 rounded-full mr-3"></span>
                                Access class schedules
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Call to Action -->
            <section class="text-center">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl p-12 text-white">
                    <h2 class="text-3xl font-bold mb-4">Ready to Streamline Your Attendance Management?</h2>
                    <p class="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
                        Join educators who have simplified their attendance tracking with Attendify's comprehensive solution
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <Link
                            v-if="!$page.props.auth.user"
                            :href="register()"
                            class="inline-flex items-center justify-center bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors"
                        >
                            Get Started Free
                        </Link>
                        <Link
                            :href="$page.props.auth.user ? dashboard() : login()"
                            class="inline-flex items-center justify-center border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition-colors"
                        >
                            {{ $page.props.auth.user ? 'Go to Dashboard' : 'Learn More' }}
                        </Link>
                    </div>
                </div>
            </section>
        </div>

        <!-- Footer -->
        <footer class="mt-20 px-6 py-8 border-t border-gray-200 dark:border-gray-700 bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm">
            <div class="max-w-7xl mx-auto text-center">
                <p class="text-gray-600 dark:text-gray-400">
                    © 2024 Attendify - Smart Attendance Management System
                </p>
            </div>
        </footer>
    </div>
</template>