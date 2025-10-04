<template>
  <div class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg">
    <div class="flex h-full flex-col">
      <!-- Logo -->
      <div class="flex h-16 items-center justify-center border-b border-gray-200">
        <div class="flex items-center gap-2">
          <GraduationCap class="h-8 w-8 text-blue-600" />
          <span class="text-xl font-bold text-gray-900">TeacherHub</span>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-4 py-6">
        <ul class="space-y-2">
          <li v-for="item in navigation" :key="item.name">
            <a
              :href="item.href"
              :class="[
                item.name.toLowerCase() === currentPage
                  ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600'
                  : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900',
                'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors'
              ]"
            >
              <component :is="item.icon" class="mr-3 h-5 w-5 flex-shrink-0" />
              {{ item.name }}
            </a>
          </li>
        </ul>
      </nav>

      <!-- User Profile -->
      <div class="border-t border-gray-200 p-4">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <Avatar class="h-8 w-8">
              <AvatarImage src="" alt="Teacher" />
              <AvatarFallback>JS</AvatarFallback>
            </Avatar>
          </div>
          <div class="ml-3 flex-1">
            <p class="text-sm font-medium text-gray-900">John Smith</p>
            <p class="text-xs text-gray-500">Professor</p>
          </div>
          <Button variant="ghost" size="sm" @click="logout">
            <LogOut class="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import {
  LayoutDashboard,
  Users,
  ClipboardList,
  BarChart3,
  FolderOpen,
  Settings,
  LogOut,
  GraduationCap
} from 'lucide-vue-next'

interface Props {
  currentPage?: string
}

const props = withDefaults(defineProps<Props>(), {
  currentPage: 'dashboard'
})

const navigation = computed(() => [
  {
    name: 'Dashboard',
    href: '/teacher/dashboard',
    icon: LayoutDashboard
  },
  {
    name: 'Classes',
    href: '/teacher/classes',
    icon: Users
  },
  {
    name: 'Attendance',
    href: '/teacher/attendance',
    icon: ClipboardList
  },
  {
    name: 'Reports',
    href: '/teacher/reports',
    icon: BarChart3
  },
  {
    name: 'Files',
    href: '/teacher/files',
    icon: FolderOpen
  },
  {
    name: 'Settings',
    href: '/teacher/settings',
    icon: Settings
  }
])

const logout = () => {
  console.log('Logout clicked')
  // Implement logout logic - you can use Inertia or form submission
  window.location.href = '/logout'
}
</script>