<!DOCTYPE html>
<html lang="en">

    <!-- Taken Tasks Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h3 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Task Management Dashboard
                </h3>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Real-time tracking progress tugas yang sedang dikerjakan tim
                </p>
            </div>
            
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
                <div class="bg-blue-50 p-6 rounded-xl border border-blue-100">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <i class="fas fa-tasks text-blue-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-blue-600">Total Tasks Taken</p>
                            <p class="text-2xl font-bold text-blue-900">5</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-yellow-50 p-6 rounded-xl border border-yellow-100">
                    <div class="flex items-center">
                        <div class="p-2 bg-yellow-100 rounded-lg">
                            <i class="fas fa-clock text-yellow-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-yellow-600">In Progress</p>
                            <p class="text-2xl font-bold text-yellow-900">3</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-green-50 p-6 rounded-xl border border-green-100">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <i class="fas fa-check-circle text-green-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-green-600">Completed</p>
                            <p class="text-2xl font-bold text-green-900">2</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-purple-50 p-6 rounded-xl border border-purple-100">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <i class="fas fa-users text-purple-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-purple-600">Active Users</p>
                            <p class="text-2xl font-bold text-purple-900">3</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Taken Tasks List -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <h4 class="text-lg font-semibold text-gray-900">Tasks Currently Taken</h4>
                </div>
                
                <div class="divide-y divide-gray-200">
                    <!-- Taken ID: 1 -->
                    <div class="p-6 hover:bg-gray-50 transition duration-150">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                            <span class="text-indigo-600 font-semibold text-sm">1</span>
                                        </div>
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <div class="flex items-center justify-between">
                                            <h5 class="text-sm font-medium text-gray-900">Task ID: 1 - Perbaiki fitur export PDF</h5>
                                            <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full">In Progress</span>
                                        </div>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <i class="fas fa-calendar mr-2"></i>
                                            <span>Taken Date: 01 Aug 2025</span>
                                            <i class="fas fa-user ml-4 mr-2"></i>
                                            <span>Assigned to: Budi Santoso (ID: 1)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Taken ID: 2 -->
                    <div class="p-6 hover:bg-gray-50 transition duration-150">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <span class="text-green-600 font-semibold text-sm">2</span>
                                        </div>
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <div class="flex items-center justify-between">
                                            <h5 class="text-sm font-medium text-gray-900">Task ID: 2 - Tambahkan grafik arus kas</h5>
                                            <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">Done</span>
                                        </div>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <i class="fas fa-calendar mr-2"></i>
                                            <span>Taken Date: 02 Aug 2025</span>
                                            <i class="fas fa-user ml-4 mr-2"></i>
                                            <span>Assigned to: Budi Santoso (ID: 1)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Taken ID: 3 -->
                    <div class="p-6 hover:bg-gray-50 transition duration-150">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                            <span class="text-indigo-600 font-semibold text-sm">3</span>
                                        </div>
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <div class="flex items-center justify-between">
                                            <h5 class="text-sm font-medium text-gray-900">Task ID: 3 - Perbaikan filter stok</h5>
                                            <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full">In Progress</span>
                                        </div>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <i class="fas fa-calendar mr-2"></i>
                                            <span>Taken Date: 03 Aug 2025</span>
                                            <i class="fas fa-user ml-4 mr-2"></i>
                                            <span>Assigned to: Dewi Lestari (ID: 4)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Taken ID: 4 -->
                    <div class="p-6 hover:bg-gray-50 transition duration-150">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <span class="text-green-600 font-semibold text-sm">4</span>
                                        </div>
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <div class="flex items-center justify-between">
                                            <h5 class="text-sm font-medium text-gray-900">Task ID: 4 - Integrasi dengan email client</h5>
                                            <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">Done</span>
                                        </div>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <i class="fas fa-calendar mr-2"></i>
                                            <span>Taken Date: 01 Aug 2025</span>
                                            <i class="fas fa-user ml-4 mr-2"></i>
                                            <span>Assigned to: Siti Aminah (ID: 2)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Taken ID: 5 -->
                    <div class="p-6 hover:bg-gray-50 transition duration-150">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                            <span class="text-indigo-600 font-semibold text-sm">5</span>
                                        </div>
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <div class="flex items-center justify-between">
                                            <h5 class="text-sm font-medium text-gray-900">Task ID: 5 - Bug absensi shift malam</h5>
                                            <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full">In Progress</span>
                                        </div>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <i class="fas fa-calendar mr-2"></i>
                                            <span>Taken Date: 02 Aug 2025</span>
                                            <i class="fas fa-user ml-4 mr-2"></i>
                                            <span>Assigned to: Dewi Lestari (ID: 4)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 flex justify-center space-x-4">
                <button class="bg-indigo-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-indigo-700 transition duration-300">
                    <i class="fas fa-plus mr-2"></i>Take New Task
                </button>
                <button class="bg-gray-100 text-gray-700 px-6 py-3 rounded-lg font-medium hover:bg-gray-200 transition duration-300">
                    <i class="fas fa-filter mr-2"></i>Filter Tasks
                </button>
                <button class="bg-green-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-green-700 transition duration-300">
                    <i class="fas fa-download mr-2"></i>Export Report
                </button>
            </div>
        </div>