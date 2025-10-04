<template>
  <Head title="Attendance" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      
      <!-- Header Section -->
      <div class="flex items-center justify-between">
        <div class="space-y-2">
          <h1 class="text-3xl font-bold tracking-tight">
            {{ props.userRole === 'admin' ? 'System Attendance Management' : 'Attendance Management' }}
          </h1>
          <p class="text-muted-foreground">
            {{ props.userRole === 'admin' 
              ? 'Monitor and manage attendance across all classes and students' 
              : 'Track, manage, and monitor student attendance for all your classes' 
            }}
          </p>
        </div>
        <div v-if="props.userRole === 'admin'" class="flex gap-2">
          <Button @click="refreshSystemData" variant="outline">
            <RefreshCw class="h-4 w-4 mr-2" />
            Refresh Data
          </Button>
          <Button @click="exportAllData">
            <Download class="h-4 w-4 mr-2" />
            Export All
          </Button>
        </div>
      </div>

      <!-- Quick Stats Cards -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <!-- Total Students -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Students</CardTitle>
            <Users class="h-5 w-5 text-blue-500" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ attendanceStats.totalStudents || 0 }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Across all classes
            </p>
          </CardContent>
        </Card>

        <!-- Present Today -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Present Today</CardTitle>
            <UserCheck class="h-5 w-5 text-green-500" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">{{ attendanceStats.presentToday || 0 }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              <span class="text-green-600">{{ attendanceStats.totalStudents && attendanceStats.presentToday ? ((attendanceStats.presentToday / attendanceStats.totalStudents) * 100).toFixed(1) : 0 }}%</span> attendance rate
            </p>
          </CardContent>
        </Card>

        <!-- Absent Today -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Absent Today</CardTitle>
            <UserX class="h-5 w-5 text-red-500" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-red-600">{{ attendanceStats.absentToday || 0 }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              {{ attendanceStats.excusedToday || 0 }} excused absences
            </p>
          </CardContent>
        </Card>

        <!-- Active Classes -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Active Classes</CardTitle>
            <BookOpen class="h-5 w-5 text-purple-500" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ attendanceStats.activeClasses || 0 }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Classes today
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Main Content Grid -->
      <div class="grid gap-6" :class="props.userRole === 'admin' ? 'lg:grid-cols-4' : 'lg:grid-cols-3'" v-if="props.userRole !== 'student'">
        
        <!-- Left Column: Quick Actions & Controls -->
        <div class="lg:col-span-1 space-y-6">
          
          <!-- Quick Actions -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <Zap class="h-5 w-5" />
                Quick Actions
              </CardTitle>
              <CardDescription>
                {{ props.userRole === 'admin' ? 'System administration and oversight' : 'Start attendance or manage students' }}
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
              <!-- Admin-specific actions -->
              <div v-if="props.userRole === 'admin'" class="space-y-3">
                <Button 
                  @click="viewAllAttendance"
                  class="w-full justify-start" 
                  size="sm"
                >
                  <Users class="mr-2 h-4 w-4" />
                  All Attendance
                </Button>
                
                <Button 
                  @click="systemReports"
                  variant="outline" 
                  class="justify-start w-full" 
                  size="sm"
                >
                  <BarChart3 class="mr-2 h-4 w-4" />
                  Reports
                </Button>
              </div>

              <!-- Teacher-specific actions -->
              <div v-else class="space-y-3">
                <Button 
                  @click="startAttendance"
                  class="w-full justify-start" 
                  size="lg"
                >
                  <QrCode class="mr-2 h-4 w-4" />
                  Start QR Attendance
                </Button>
                
                <Button 
                  @click="viewReports"
                  variant="outline" 
                  class="w-full justify-start" 
                  size="lg"
                >
                  <FileText class="mr-2 h-4 w-4" />
                  View Reports
                </Button>
                
                <div class="flex gap-2">
                  <Button 
                    @click="exportData"
                    variant="outline" 
                    class="flex-1 justify-start" 
                    size="lg"
                    :disabled="!selectedClass || isLoading"
                  >
                    <Download class="mr-2 h-4 w-4" />
                    {{ isLoading ? 'Exporting...' : 'Export Today' }}
                  </Button>
                  <Button 
                    @click="exportWithDateRange"
                    variant="outline" 
                    class="flex-1 justify-start" 
                    size="lg"
                    :disabled="isLoading"
                  >
                    <Download class="mr-2 h-4 w-4" />
                    {{ isLoading ? 'Exporting...' : 'Export Range' }}
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Class Selection -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <BookOpen class="h-5 w-5" />
                {{ props.userRole === 'admin' ? 'Select Class (All System)' : 'Select Class' }}
              </CardTitle>
              <CardDescription>
                {{ props.userRole === 'admin' ? 'View attendance for any class in the system' : 'Choose a class to view attendance' }}
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
              <!-- Admin can view all classes -->
              <div v-if="props.userRole === 'admin'" class="space-y-2">
                <Label for="all-classes-toggle">View Mode</Label>
                <div class="flex items-center space-x-2">
                  <Button 
                    @click="showAllClasses = !showAllClasses" 
                    variant="outline" 
                    size="sm"
                    class="w-full"
                  >
                    {{ showAllClasses ? 'Show Individual Class' : 'Show All Classes' }}
                  </Button>
                </div>
              </div>

              <div class="space-y-2" v-if="!showAllClasses || props.userRole !== 'admin'">
                <Label for="class-select">Class</Label>
                <Select v-model="selectedClass" @update:modelValue="(value) => onClassChange(value as string)">
                  <SelectTrigger>
                    <SelectValue placeholder="Select a class" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="cls in classes" :key="cls.id" :value="cls.id">
                      {{ cls.name }} 
                      <span v-if="props.userRole === 'admin'" class="text-muted-foreground">
                        ({{ cls.teacher }}) - {{ cls.studentCount }} students
                      </span>
                      <span v-else>
                        ({{ cls.studentCount }} students)
                      </span>
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
              
              <div class="space-y-2">
                <Label for="date-select" class="text-sm font-medium">Date</Label>
                <div class="relative">
                  <Calendar class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4 pointer-events-none" />
                  <Input 
                    type="date" 
                    v-model="selectedDate"
                    @change="onDateChange"
                    id="date-select"
                    class="pl-10 [&::-webkit-calendar-picker-indicator]:hidden [&::-webkit-calendar-picker-indicator]:absolute [&::-webkit-calendar-picker-indicator]:right-2 [&::-webkit-calendar-picker-indicator]:top-1/2 [&::-webkit-calendar-picker-indicator]:transform [&::-webkit-calendar-picker-indicator]:-translate-y-1/2"
                    placeholder="Select date"
                  />
                </div>
              </div>

              <!-- Export Date Range (Teachers/Admins) -->
              <div v-if="props.userRole !== 'student'" class="space-y-3 pt-3 border-t">
                <Label class="text-sm font-medium">Export Date Range</Label>
                <div class="grid grid-cols-2 gap-3">
                  <div class="space-y-1">
                    <Label for="export-start-date" class="text-xs text-gray-600">From Date</Label>
                    <div class="relative">
                      <Calendar class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4 pointer-events-none" />
                      <Input 
                        type="date" 
                        v-model="exportStartDate"
                        id="export-start-date"
                        class="text-sm pl-10 [&::-webkit-calendar-picker-indicator]:opacity-0"
                        placeholder="Select start date"
                      />
                    </div>
                  </div>
                  <div class="space-y-1">
                    <Label for="export-end-date" class="text-xs text-gray-600">To Date</Label>
                    <div class="relative">
                      <Calendar class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4 pointer-events-none" />
                      <Input 
                        type="date" 
                        v-model="exportEndDate"
                        id="export-end-date" 
                        class="text-sm pl-10 [&::-webkit-calendar-picker-indicator]:opacity-0"
                        placeholder="Select end date"
                      />
                    </div>
                  </div>
                </div>
                <Button 
                  @click="exportWithDateRange" 
                  variant="outline" 
                  size="sm" 
                  class="w-full mt-2"
                  :disabled="isLoading"
                >
                  <Download class="h-4 w-4 mr-2" />
                  {{ isLoading ? 'Exporting...' : 'Export Range to CSV' }}
                </Button>
              </div>
              
              <div v-if="selectedClass && !showAllClasses" class="pt-2 border-t">
                <div class="text-sm text-muted-foreground">
                  <div class="flex justify-between">
                    <span>Students:</span>
                    <span>{{ getClassById(selectedClass)?.studentCount || 0 }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span>Present:</span>
                    <span class="text-green-600">{{ students.filter(s => s.status === 'present').length }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span>Absent:</span>
                    <span class="text-red-600">{{ students.filter(s => s.status === 'absent').length }}</span>
                  </div>
                  <div v-if="props.userRole === 'admin'" class="flex justify-between">
                    <span>Teacher:</span>
                    <span>{{ getClassById(selectedClass)?.teacher || 'N/A' }}</span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Right Column: Attendance List & Sessions -->
        <div :class="props.userRole === 'admin' ? 'lg:col-span-3' : 'lg:col-span-2'" class="space-y-6">
          
          <!-- Today's Sessions (Teachers only) -->
          <Card v-if="userRole === 'teacher'">
            <CardHeader>
              <div class="flex items-center justify-between">
                <div>
                  <CardTitle class="flex items-center gap-2">
                    <Clock class="h-5 w-5" />
                    Today's Sessions
                  </CardTitle>
                  <CardDescription>{{ formatDate(selectedDate) }}</CardDescription>
                </div>
                <Button @click="refreshSessions" variant="outline" size="sm">
                  <RefreshCw class="h-4 w-4 mr-1" />
                  Refresh
                </Button>
              </div>
            </CardHeader>
            <CardContent>
              <div class="space-y-3">
                <div 
                  v-for="session in todaySessions" 
                  :key="session.id"
                  class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 transition-colors"
                >
                  <div class="flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full"
                         :class="{
                           'bg-green-500': session.status === 'completed',
                           'bg-blue-500': session.status === 'active',
                           'bg-gray-400': session.status === 'scheduled'
                         }"></div>
                    <div>
                      <p class="font-medium">{{ session.className }}</p>
                      <p class="text-sm text-muted-foreground">
                        {{ session.time }} • {{ session.presentCount }}/{{ session.totalStudents }} present
                      </p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <Badge 
                      :variant="session.status === 'completed' ? 'default' : session.status === 'active' ? 'secondary' : 'outline'"
                    >
                      {{ session.status === 'completed' ? 'Completed' : session.status === 'active' ? 'Active' : 'Scheduled' }}
                    </Badge>
                    <Button 
                      v-if="session.status !== 'completed'"
                      @click="manageSession(session)"
                      variant="ghost" 
                      size="sm"
                    >
                      <Settings class="h-4 w-4" />
                    </Button>
                  </div>
                </div>
                
                <div v-if="todaySessions.length === 0" class="text-center py-8 text-muted-foreground">
                  <Calendar class="mx-auto h-8 w-8 mb-2" />
                  <p>No sessions scheduled for today</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Student Attendance List -->
          <Card v-if="selectedClass && !showAllClasses">
            <CardHeader>
              <div class="flex items-center justify-between">
                <div>
                  <CardTitle class="flex items-center gap-2">
                    <Users class="h-5 w-5" />
                    Student Attendance
                  </CardTitle>
                  <CardDescription>{{ getClassById(selectedClass)?.name }} - {{ formatDate(selectedDate) }}</CardDescription>
                </div>
                <div class="flex gap-2">
                  <Button @click="markAllPresent" variant="outline" size="sm">
                    <UserCheck class="h-4 w-4 mr-1" />
                    Mark All Present
                  </Button>
                  <Button @click="saveAttendance" size="sm">
                    <Save class="h-4 w-4 mr-1" />
                    Save Changes
                  </Button>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <div class="space-y-2">
                <!-- Search -->
                <div class="relative mb-4">
                  <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                  <Input
                    v-model="searchStudent"
                    placeholder="Search students..."
                    class="pl-10"
                  />
                </div>
                
                <!-- Students List -->
                <div class="space-y-2 max-h-96 overflow-y-auto">
                  <div 
                    v-for="student in filteredStudents" 
                    :key="student.id"
                    class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 transition-colors"
                  >
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-sm font-medium">
                        {{ student.name.charAt(0) }}
                      </div>
                      <div>
                        <p class="font-medium">{{ student.name }}</p>
                        <p class="text-sm text-muted-foreground">{{ student.studentId }}</p>
                      </div>
                    </div>
                    <div class="flex items-center gap-2">
                      <Select v-model="student.status" @update:modelValue="(value) => updateStudentStatus(student.id, value as string)">
                        <SelectTrigger class="w-32">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="present">Present</SelectItem>
                          <SelectItem value="absent">Absent</SelectItem>
                          <SelectItem value="excused">Excused</SelectItem>
                          <SelectItem value="late">Late</SelectItem>
                        </SelectContent>
                      </Select>
                      <Badge 
                        :class="{
                          'bg-green-100 text-green-800': student.status === 'present',
                          'bg-red-100 text-red-800': student.status === 'absent',
                          'bg-yellow-100 text-yellow-800': student.status === 'excused',
                          'bg-orange-100 text-orange-800': student.status === 'late'
                        }"
                      >
                        {{ student.status }}
                      </Badge>
                    </div>
                  </div>
                </div>
                
                <div v-if="filteredStudents.length === 0" class="text-center py-8 text-muted-foreground">
                  <Users class="mx-auto h-8 w-8 mb-2" />
                  <p>No students found</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- System-wide Attendance Overview (Admin Only) -->
          <Card v-if="showAllClasses && props.userRole === 'admin'">
            <CardHeader>
              <div class="flex items-center justify-between">
                <div>
                  <CardTitle class="flex items-center gap-2">
                    <Users class="h-5 w-5" />
                    System Overview
                  </CardTitle>
                  <CardDescription>All students - {{ formatDate(selectedDate) }}</CardDescription>
                </div>
                <div class="flex gap-2">
                  <Button @click="refreshSystemData" variant="outline" size="sm">
                    <RefreshCw class="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <div class="space-y-6">
                <!-- Enhanced Summary Stats -->
                <div class="grid grid-cols-5 gap-3">
                  <div class="text-center p-3 bg-blue-50 rounded-lg border border-blue-100">
                    <div class="text-2xl font-bold text-blue-600">{{ systemSummary.total || 0 }}</div>
                    <div class="text-xs text-blue-600 font-medium">Total</div>
                  </div>
                  <div class="text-center p-3 bg-green-50 rounded-lg border border-green-100">
                    <div class="text-2xl font-bold text-green-600">{{ systemSummary.present || 0 }}</div>
                    <div class="text-xs text-green-600 font-medium">Present</div>
                  </div>
                  <div class="text-center p-3 bg-red-50 rounded-lg border border-red-100">
                    <div class="text-2xl font-bold text-red-600">{{ systemSummary.absent || 0 }}</div>
                    <div class="text-xs text-red-600 font-medium">Absent</div>
                  </div>
                  <div class="text-center p-3 bg-yellow-50 rounded-lg border border-yellow-100">
                    <div class="text-2xl font-bold text-yellow-600">{{ systemSummary.excused || 0 }}</div>
                    <div class="text-xs text-yellow-600 font-medium">Excused</div>
                  </div>
                  <div class="text-center p-3 bg-gray-50 rounded-lg border border-gray-100">
                    <div class="text-2xl font-bold text-gray-600">{{ systemSummary.no_record || 0 }}</div>
                    <div class="text-xs text-gray-600 font-medium">No Record</div>
                  </div>
                </div>

                <!-- Search and Filter Bar -->
                <div class="flex gap-3">
                  <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                    <Input
                      v-model="searchStudent"
                      placeholder="Search students..."
                      class="pl-10"
                    />
                  </div>
                  <Select v-model="statusFilter">
                    <SelectTrigger class="w-32">
                      <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">All</SelectItem>
                      <SelectItem value="present">Present</SelectItem>
                      <SelectItem value="absent">Absent</SelectItem>
                      <SelectItem value="excused">Excused</SelectItem>
                      <SelectItem value="no-record">No Record</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                
                <!-- Students List with Enhanced Layout -->
                <div class="space-y-2 max-h-80 overflow-y-auto">
                  <div 
                    v-for="student in filteredStudents" 
                    :key="student.id"
                    class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 transition-colors bg-white"
                  >
                    <div class="flex items-center gap-4">
                      <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-medium">
                        {{ student.name.charAt(0).toUpperCase() }}
                      </div>
                      <div>
                        <p class="font-semibold text-gray-900">{{ student.name }}</p>
                        <p class="text-sm text-gray-500">{{ student.studentId }}</p>
                      </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                      <div class="text-right">
                        <Badge 
                          :class="{
                            'bg-green-100 text-green-800 border-green-200': student.status === 'present',
                            'bg-red-100 text-red-800 border-red-200': student.status === 'absent',
                            'bg-yellow-100 text-yellow-800 border-yellow-200': student.status === 'excused',
                            'bg-orange-100 text-orange-800 border-orange-200': student.status === 'late',
                            'bg-gray-100 text-gray-800 border-gray-200': student.status === 'no-record'
                          }"
                          class="font-medium"
                        >
                          {{ student.status === 'no-record' ? 'No Record' : student.status.charAt(0).toUpperCase() + student.status.slice(1) }}
                        </Badge>
                      </div>
                    </div>
                  </div>
                </div>
                
                <div v-if="filteredStudents.length === 0" class="text-center py-12 text-muted-foreground">
                  <Users class="mx-auto h-12 w-12 mb-3 text-gray-300" />
                  <p class="text-lg font-medium mb-1">No students found</p>
                  <p class="text-sm">Try adjusting your search or filter criteria</p>
                </div>
              </div>
            </CardContent>
          </Card>
          
          <!-- Placeholder when no class selected -->
          <Card v-if="!selectedClass && !showAllClasses">
            <CardContent class="text-center py-12">
              <BookOpen class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
              <h3 class="text-lg font-semibold mb-2">
                {{ props.userRole === 'admin' ? 'Select View Mode' : 'Select a Class' }}
              </h3>
              <p class="text-muted-foreground">
                {{ props.userRole === 'admin' 
                  ? 'Choose to view individual class attendance or system-wide overview' 
                  : 'Choose a class from the sidebar to view and manage attendance' 
                }}
              </p>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Student View -->
      <div v-if="props.userRole === 'student'" class="space-y-6">
        
        <!-- Student Stats -->
        <div class="grid gap-4 md:grid-cols-4">
          <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle class="text-sm font-medium">Total Days</CardTitle>
              <Calendar class="h-5 w-5 text-blue-500" />
            </CardHeader>
            <CardContent>
              <div class="text-2xl font-bold">{{ attendanceStats.totalDays || 0 }}</div>
              <p class="text-xs text-muted-foreground mt-1">
                This semester
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle class="text-sm font-medium">Present Days</CardTitle>
              <CheckCircle class="h-5 w-5 text-green-500" />
            </CardHeader>
            <CardContent>
              <div class="text-2xl font-bold text-green-600">{{ attendanceStats.presentDays || 0 }}</div>
              <p class="text-xs text-muted-foreground mt-1">
                <span class="text-green-600">{{ attendanceStats.attendanceRate || 0 }}%</span> attendance rate
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle class="text-sm font-medium">Absent Days</CardTitle>
              <AlertCircle class="h-5 w-5 text-red-500" />
            </CardHeader>
            <CardContent>
              <div class="text-2xl font-bold text-red-600">{{ attendanceStats.absentDays || 0 }}</div>
              <p class="text-xs text-muted-foreground mt-1">
                {{ attendanceStats.excusedDays || 0 }} excused
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle class="text-sm font-medium">Attendance Rate</CardTitle>
              <Users class="h-5 w-5 text-purple-500" />
            </CardHeader>
            <CardContent>
              <div class="text-2xl font-bold">{{ attendanceStats.attendanceRate || 0 }}%</div>
              <p class="text-xs text-muted-foreground mt-1">
                Overall performance
              </p>
            </CardContent>
          </Card>
        </div>

        <!-- Attendance History -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Clock class="h-5 w-5" />
              My Attendance History
            </CardTitle>
            <CardDescription>View your attendance records and submit excuses</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div 
                v-for="record in props.attendanceHistory" 
                :key="record.id"
                class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 transition-colors"
              >
                <div class="flex items-center gap-3">
                  <div class="w-2 h-2 rounded-full"
                       :class="{
                         'bg-green-500': record.status === 'present',
                         'bg-red-500': record.status === 'absent',
                         'bg-yellow-500': record.status === 'excused',
                         'bg-orange-500': record.status === 'late'
                       }"></div>
                  <div>
                    <p class="font-medium">{{ record.class_name }}</p>
                    <p class="text-sm text-muted-foreground">
                      {{ formatDate(record.date) }}
                      <span v-if="record.time_in"> • {{ record.time_in }}</span>
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <Badge 
                    :class="{
                      'bg-green-100 text-green-800': record.status === 'present',
                      'bg-red-100 text-red-800': record.status === 'absent',
                      'bg-yellow-100 text-yellow-800': record.status === 'excused',
                      'bg-orange-100 text-orange-800': record.status === 'late'
                    }"
                  >
                    {{ record.status }}
                  </Badge>
                  <Button 
                    v-if="record.status === 'absent' && !record.excuse"
                    @click="openExcuseDialog(record)"
                    variant="outline" 
                    size="sm"
                  >
                    Submit Excuse
                  </Button>
                  <Badge 
                    v-if="record.excuse"
                    :variant="record.excuse.status === 'approved' ? 'default' : record.excuse.status === 'pending' ? 'secondary' : 'destructive'"
                  >
                    Excuse {{ record.excuse.status }}
                  </Badge>
                </div>
              </div>
              
              <div v-if="props.attendanceHistory.length === 0" class="text-center py-8 text-muted-foreground">
                <Calendar class="mx-auto h-8 w-8 mb-2" />
                <p>No attendance records found</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

    </div>

    <!-- QR Scanner Modal -->
    <Dialog v-model:open="showQRScanner">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>QR Code Attendance</DialogTitle>
          <DialogDescription>
            Students can scan this QR code to mark their attendance
          </DialogDescription>
        </DialogHeader>
        <div class="flex justify-center py-6">
          <div class="w-48 h-48 bg-gray-100 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center">
            <QrCode class="h-16 w-16 text-gray-400" />
          </div>
        </div>
        <div class="text-center text-sm text-muted-foreground">
          Class: {{ getClassById(selectedClass)?.name }}<br>
          Session ID: {{ currentSessionId }}
        </div>
        <DialogFooter>
          <Button @click="showQRScanner = false" variant="outline">Close</Button>
          <Button @click="endSession">End Session</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Excuse Submission Dialog -->
    <Dialog v-model:open="showExcuseDialog">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Submit Excuse</DialogTitle>
          <DialogDescription>
            Provide a reason for your absence and any supporting documentation
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-4">
          <div class="space-y-2">
            <Label for="excuse-reason">Reason for Absence</Label>
            <textarea
              id="excuse-reason"
              v-model="excuseReason"
              placeholder="Please explain the reason for your absence..."
              class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            ></textarea>
          </div>
          
          <div class="space-y-2">
            <Label for="supporting-doc">Supporting Document (Optional)</Label>
            <Input
              id="supporting-doc"
              type="file"
              accept=".pdf,.jpg,.jpeg,.png"
              @change="(e: Event) => supportingDocument = (e.target as HTMLInputElement)?.files?.[0] || null"
            />
            <p class="text-xs text-muted-foreground">
              PDF, JPG, JPEG, or PNG files only (max 2MB)
            </p>
          </div>
        </div>
        <DialogFooter>
          <Button @click="showExcuseDialog = false" variant="outline">Cancel</Button>
          <Button @click="submitExcuse" :disabled="!excuseReason.trim() || isLoading">
            {{ isLoading ? 'Submitting...' : 'Submit Excuse' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { 
  Users, UserCheck, UserX, BookOpen, Clock, Calendar, QrCode, Zap, 
  FileText, FileCheck, Download, Search, Settings, RefreshCw, Save, AlertCircle, CheckCircle, BarChart3
} from 'lucide-vue-next';
import { ref, computed, onMounted } from 'vue';

// Props from backend
interface Props {
  userRole: 'teacher' | 'admin' | 'student';
  classes?: Array<{
    id: string;
    name: string;
    studentCount: number;
    schedule: string;
    teacher?: string;
  }>;
  attendanceStats: {
    totalStudents?: number;
    presentToday?: number;
    absentToday?: number;
    excusedToday?: number;
    activeClasses?: number;
    // Student specific stats
    totalDays?: number;
    presentDays?: number;
    absentDays?: number;
    excusedDays?: number;
    attendanceRate?: number;
  };
  todaySessions?: Array<{
    id: string;
    className: string;
    time: string;
    status: 'completed' | 'active' | 'scheduled';
    presentCount: number;
    totalStudents: number;
    classId: string;
    teacher?: string;
  }>;
  student?: any;
  attendanceHistory?: Array<{
    id: string;
    date: string;
    status: string;
    time_in: string | null;
    time_out: string | null;
    class_name: string;
    excuse: any;
  }>;
}

const props = withDefaults(defineProps<Props>(), {
  classes: () => [],
  todaySessions: () => [],
  attendanceHistory: () => [],
});

// Page properties
const page = usePage();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Attendance', href: '#' }
];

// Reactive data
const selectedClass = ref('');
const selectedDate = ref(new Date().toISOString().split('T')[0]);
const searchStudent = ref('');
const statusFilter = ref('all');
const showQRScanner = ref(false);
const currentSessionId = ref('');
const showAllClasses = ref(false); // For admin view toggle
const students = ref<Array<{
  id: string;
  name: string;
  studentId: string;
  status: string;
  classId?: string;
}>>([]);
const isLoading = ref(false);
const showExcuseDialog = ref(false);
const selectedAttendanceForExcuse = ref<any>(null);
const excuseReason = ref('');
const supportingDocument = ref<File | null>(null);
const systemSummary = ref({
  total: 0,
  present: 0,
  absent: 0,
  excused: 0,
  no_record: 0,
  attendance_rate: 0
});
const exportStartDate = ref(new Date().toISOString().split('T')[0]);
const exportEndDate = ref(new Date().toISOString().split('T')[0]);

// Computed properties
const filteredStudents = computed(() => {
  let filtered = students.value;
  
  // Filter by search query
  if (searchStudent.value) {
    const query = searchStudent.value.toLowerCase();
    filtered = filtered.filter(student => 
      student.name.toLowerCase().includes(query) ||
      student.studentId.toLowerCase().includes(query)
    );
  }
  
  // Filter by status
  if (statusFilter.value && statusFilter.value !== 'all') {
    filtered = filtered.filter(student => student.status === statusFilter.value);
  }
  
  return filtered;
});

// Methods
const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const getClassById = (classId: string) => {
  return props.classes?.find(cls => cls.id === classId);
};

const onClassChange = async (classId: string) => {
  console.log('Class changed to:', classId);
  searchStudent.value = '';
  
  if (classId && props.userRole !== 'student') {
    await loadClassStudents(classId, selectedDate.value);
  }
};

const onDateChange = async () => {
  console.log('Date changed to:', selectedDate.value);
  
  if (showAllClasses.value && props.userRole === 'admin') {
    // Load system-wide data for admin
    await loadSystemAttendance();
  } else if (selectedClass.value && props.userRole !== 'student') {
    // Load specific class data for teacher/admin
    await loadClassStudents(selectedClass.value, selectedDate.value);
  }
};

const loadClassStudents = async (classId: string, date: string) => {
  try {
    isLoading.value = true;
    const response = await fetch(`/attendance/class/${classId}/students?date=${date}`, {
      method: 'GET',
      headers: {
        'X-CSRF-TOKEN': page.props.csrf_token as string,
        'Accept': 'application/json',
      },
    });

    if (response.ok) {
      const data = await response.json();
      students.value = data;
    } else {
      console.error('Failed to load students');
    }
  } catch (error) {
    console.error('Error loading students:', error);
  } finally {
    isLoading.value = false;
  }
};

const startAttendance = () => {
  if (!selectedClass.value) {
    alert('Please select a class first');
    return;
  }
  currentSessionId.value = 'SESSION-' + Date.now();
  showQRScanner.value = true;
};

const viewReports = () => {
  router.visit('/reports');
};

const exportData = async () => {
  if (!selectedClass.value) {
    alert('Please select a class first');
    return;
  }

  try {
    isLoading.value = true;
    const response = await fetch('/attendance/export', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': page.props.csrf_token as string,
        'Content-Type': 'application/json',
        'Accept': 'text/csv',
      },
      body: JSON.stringify({
        class_id: selectedClass.value,
        start_date: selectedDate.value,
        end_date: selectedDate.value,
        format: 'class',
      }),
    });

    if (response.ok) {
      // Handle CSV file download
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      
      // Extract filename from response headers or create a default one
      const contentDisposition = response.headers.get('Content-Disposition');
      let filename = 'attendance_export.csv';
      
      if (contentDisposition) {
        const filenameMatch = contentDisposition.match(/filename="(.+)"/);
        if (filenameMatch) {
          filename = filenameMatch[1];
        }
      } else {
        // Create filename based on class and date
        const className = getClassById(selectedClass.value)?.name || 'class';
        const cleanClassName = className.replace(/[^a-zA-Z0-9]/g, '_');
        filename = `attendance_${cleanClassName}_${selectedDate.value}.csv`;
      }
      
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
      
      alert('Attendance data exported successfully!');
    } else {
      const errorData = await response.json().catch(() => ({ error: 'Export failed' }));
      alert(errorData.error || 'Export failed. Please try again.');
    }
  } catch (error) {
    console.error('Export error:', error);
    alert('Export failed. Please try again.');
  } finally {
    isLoading.value = false;
  }
};

const convertToCSV = (data: any[]) => {
  if (data.length === 0) return '';
  
  const headers = Object.keys(data[0]);
  const csvContent = [
    headers.join(','),
    ...data.map(row => headers.map(header => `"${row[header] || ''}"`).join(','))
  ].join('\n');
  
  return csvContent;
};

const downloadCSV = (content: string, filename: string) => {
  const blob = new Blob([content], { type: 'text/csv' });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  window.URL.revokeObjectURL(url);
};

const updateStudentStatus = (studentId: string, status: string) => {
  const student = students.value.find(s => s.id === studentId);
  if (student) {
    student.status = status;
  }
};

const markAllPresent = () => {
  students.value.forEach(student => {
    student.status = 'present';
  });
};

const saveAttendance = async () => {
  if (!selectedClass.value || students.value.length === 0) {
    alert('No students to save attendance for');
    return;
  }

  try {
    isLoading.value = true;
    const response = await fetch('/attendance/record', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': page.props.csrf_token as string,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        class_id: selectedClass.value,
        date: selectedDate.value,
        students: students.value.map(student => ({
          student_id: student.id,
          status: student.status,
          time_in: student.status === 'present' ? '08:00' : null,
          time_out: student.status === 'present' ? '10:00' : null,
        })),
      }),
    });

    if (response.ok) {
      const data = await response.json();
      alert(data.message);
    } else {
      const error = await response.json();
      alert(error.error || 'Failed to save attendance');
    }
  } catch (error) {
    console.error('Save error:', error);
    alert('Failed to save attendance. Please try again.');
  } finally {
    isLoading.value = false;
  }
};

const submitExcuse = async () => {
  if (!selectedAttendanceForExcuse.value || !excuseReason.value.trim()) {
    alert('Please provide a reason for the excuse');
    return;
  }

  try {
    isLoading.value = true;
    const formData = new FormData();
    formData.append('attendance_id', selectedAttendanceForExcuse.value.id);
    formData.append('reason', excuseReason.value);
    if (supportingDocument.value) {
      formData.append('supporting_document', supportingDocument.value);
    }

    const response = await fetch('/attendance/excuse', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': page.props.csrf_token as string,
        'Accept': 'application/json',
      },
      body: formData,
    });

    if (response.ok) {
      const data = await response.json();
      alert(data.message);
      showExcuseDialog.value = false;
      excuseReason.value = '';
      supportingDocument.value = null;
      selectedAttendanceForExcuse.value = null;
      // Refresh page data
      router.reload();
    } else {
      const error = await response.json();
      alert(error.error || 'Failed to submit excuse');
    }
  } catch (error) {
    console.error('Excuse submission error:', error);
    alert('Failed to submit excuse. Please try again.');
  } finally {
    isLoading.value = false;
  }
};

const refreshSessions = () => {
  router.reload();
};

const manageSession = (session: any) => {
  selectedClass.value = session.classId;
  if (props.userRole !== 'student') {
    loadClassStudents(session.classId, selectedDate.value);
  }
};

const endSession = () => {
  showQRScanner.value = false;
  currentSessionId.value = '';
  alert('Attendance session ended');
};

const openExcuseDialog = (attendance: any) => {
  selectedAttendanceForExcuse.value = attendance;
  showExcuseDialog.value = true;
};

// Admin-specific methods
const viewAllAttendance = () => {
  showAllClasses.value = true;
  selectedClass.value = '';
  // Load system-wide attendance data
  loadSystemAttendance();
};

const manageExcuses = () => {
  router.visit('/attendance/excuses');
};

const systemReports = () => {
  router.visit('/reports');
};

const exportAllData = async () => {
  try {
    isLoading.value = true;
    const response = await fetch('/attendance/export', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': page.props.csrf_token as string,
        'Content-Type': 'application/json',
        'Accept': 'text/csv',
      },
      body: JSON.stringify({
        export_all: true, // Admin flag to export all system data
        start_date: selectedDate.value,
        end_date: selectedDate.value,
        format: 'system',
      }),
    });

    if (response.ok) {
      // Handle CSV file download
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      
      // Create filename for system export
      const filename = `system_attendance_${selectedDate.value}.csv`;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
      
      alert('System attendance data exported successfully!');
    } else {
      const errorData = await response.json().catch(() => ({ error: 'Export failed' }));
      alert(errorData.error || 'Export failed. Please try again.');
    }
  } catch (error) {
    console.error('Export error:', error);
    alert('Export failed. Please try again.');
  } finally {
    isLoading.value = false;
  }
};

const loadSystemAttendance = async () => {
  if (props.userRole !== 'admin') return;

  try {
    isLoading.value = true;
    const response = await fetch(`/attendance/system-overview?date=${selectedDate.value}`, {
      method: 'GET',
      headers: {
        'X-CSRF-TOKEN': page.props.csrf_token as string,
        'Accept': 'application/json',
      },
    });

    if (response.ok) {
      const data = await response.json();
      // Handle system-wide data display
      students.value = data.students || [];
      systemSummary.value = data.summary || systemSummary.value;
    } else {
      console.error('Failed to load system attendance');
    }
  } catch (error) {
    console.error('Error loading system attendance:', error);
  } finally {
    isLoading.value = false;
  }
};

const refreshSystemData = () => {
  if (showAllClasses.value && props.userRole === 'admin') {
    loadSystemAttendance();
  } else {
    router.reload();
  }
};

const exportWithDateRange = async () => {
  if (!exportStartDate.value || !exportEndDate.value) {
    alert('Please select both start and end dates');
    return;
  }

  if (new Date(exportStartDate.value) > new Date(exportEndDate.value)) {
    alert('Start date cannot be after end date');
    return;
  }

  try {
    isLoading.value = true;
    const response = await fetch('/attendance/export', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': page.props.csrf_token as string,
        'Content-Type': 'application/json',
        'Accept': 'text/csv',
      },
      body: JSON.stringify({
        class_id: selectedClass.value || null,
        start_date: exportStartDate.value,
        end_date: exportEndDate.value,
        format: selectedClass.value ? 'class' : 'teacher',
      }),
    });

    if (response.ok) {
      // Handle CSV file download
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      
      // Create filename based on selection
      let filename;
      if (selectedClass.value) {
        const className = getClassById(selectedClass.value)?.name || 'class';
        const cleanClassName = className.replace(/[^a-zA-Z0-9]/g, '_');
        filename = `attendance_${cleanClassName}_${exportStartDate.value}_to_${exportEndDate.value}.csv`;
      } else {
        filename = `attendance_all_classes_${exportStartDate.value}_to_${exportEndDate.value}.csv`;
      }
      
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
      
      alert('Attendance data exported successfully!');
    } else {
      const errorData = await response.json().catch(() => ({ error: 'Export failed' }));
      alert(errorData.error || 'Export failed. Please try again.');
    }
  } catch (error) {
    console.error('Export error:', error);
    alert('Export failed. Please try again.');
  } finally {
    isLoading.value = false;
  }
};

// Initialize
onMounted(async () => {
  // Set first class as default if available
  if (props.classes && props.classes.length > 0 && props.userRole !== 'student') {
    selectedClass.value = props.classes[0].id;
    await loadClassStudents(selectedClass.value, selectedDate.value);
  }
});
</script>