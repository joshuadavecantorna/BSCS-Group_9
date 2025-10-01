<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, Users, History, FileText, Settings, LogOut } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const studentNavItems: NavItem[] = [
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
    icon: History,
  },
  {
    title: 'Excuse Requests',
    href: '/student/excuse-requests',
    icon: FileText,
  },
  {
    title: 'Settings',
    href: '/student/settings',
    icon: Settings,
  },
];

const footerNavItems: NavItem[] = [
  {
    title: 'Logout',
    href: '/logout',
    icon: LogOut,
  },
];
</script>

<template>
  <Sidebar class="border-sidebar-border bg-sidebar text-sidebar-foreground">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <Link href="/student/dashboard">
              <div class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                <AppLogo class="size-4" />
              </div>
              <div class="grid flex-1 text-left text-sm leading-tight">
                <span class="truncate font-semibold">Attendify</span>
                <span class="truncate text-xs">Student Portal</span>
              </div>
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>
    
    <SidebarContent>
      <NavMain :items="studentNavItems" />
    </SidebarContent>
    
    <SidebarFooter>
      <NavUser />
      <NavFooter :items="footerNavItems" />
    </SidebarFooter>
  </Sidebar>
</template>
