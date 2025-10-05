<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Users, ClipboardCheck, BarChart3, FileText, GraduationCap, Settings } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);

// Check if user is a teacher
const isTeacher = computed(() => user.value?.isTeacher === true);
const isStudent = computed(() => user.value?.role === 'student' || user.value?.isStudent === true);

// Dynamic navigation based on user role
const mainNavItems = computed((): NavItem[] => {
  if (isTeacher.value) {
    // Teacher Navigation
    return [
      {
        title: 'Dashboard',
        href: '/teacher/dashboard',
        icon: LayoutGrid,
      },
      {
        title: 'Class Management',
        href: '/teacher/classes',
        icon: Users,
      },
      {
        title: 'Attendance',
        href: '/teacher/attendance',
        icon: ClipboardCheck,
      },
      {
        title: 'Reports',
        href: '/teacher/reports',
        icon: BarChart3,
      },
      {
        title: 'Files',
        href: '/teacher/files',
        icon: FileText,
      },
    ];
  } else if (isStudent.value) {
    // Student Navigation
    return [
      {
        title: 'Dashboard',
        href: '/student/dashboard',
        icon: LayoutGrid,
      },
      {
        title: 'Classes',
        href: '/student/classes',
        icon: Users,
      },
      {
        title: 'Attendance History',
        href: '/student/attendance-history',
        icon: ClipboardCheck,
      },
      {
        title: 'Excuse Requests',
        href: '/student/excuse-requests',
        icon: FileText,
      },
    ];
  } else {
    // Admin Navigation (default)
    return [
      {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
      },
      {
        title: 'Teachers',
        href: '/admin/teachers',
        icon: GraduationCap,
      },
      {
        title: 'Reports',
        href: '/admin/reports',
        icon: BarChart3,
      },
      {
        title: 'Settings',
        href: '/admin/settings',
        icon: Settings,
      },
    ];
  }
});

const footerNavItems: NavItem[] = [
  {
    title: 'Github Repo',
    href: 'https://github.com/laravel/vue-starter-kit',
    icon: Folder,
  },
  {
    title: 'Documentation',
    href: 'https://laravel.com/docs/starter-kits#vue',
    icon: BookOpen,
  },
];
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <Link :href="isTeacher ? '/teacher/dashboard' : (isStudent ? '/student/dashboard' : '/dashboard')">
              <AppLogo />
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
      <NavMain :items="mainNavItems" />
    </SidebarContent>

    <SidebarFooter>
      <NavFooter :items="footerNavItems" />
      <NavUser />
    </SidebarFooter>
  </Sidebar>
  <slot />
</template>
