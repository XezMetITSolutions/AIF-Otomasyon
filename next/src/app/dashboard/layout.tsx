"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { motion, AnimatePresence } from "framer-motion";
import { 
  Gauge, Users, Calendar, Megaphone, 
  Settings, FolderKanban, LogOut, Menu, X, 
  UserCircle, Bell, ChevronDown, ShieldCheck, 
  Building, Sitemap, Sliders, ClipboardList, 
  Box, FilePieChart, MailOpen
} from "lucide-react";
import { getProfileAction, logoutAction } from "../actions/auth";

// Menüler kategori bazlı gruplanır
const baseMenu = [
  {
    title: "GENEL",
    links: [
      { href: "/dashboard", label: "Kontrol Paneli", icon: Gauge, match: "/dashboard" },
      { href: "/dashboard/duyurular", label: "Duyurular", icon: Megaphone, match: "duyurular" },
      { href: "/dashboard/takvim", label: "Çalışma Takvimi", icon: Calendar, match: "takvim" },
      { href: "/dashboard/toplantilar", label: "Toplantılar", icon: Users, match: "toplantilar" },
      { href: "/dashboard/uyeler", label: "Üyeler", icon: Users, match: "uyeler" },
    ]
  }
];

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [mobileOpen, setMobileOpen] = useState(false);
  const [user, setUser] = useState<any>(null);
  const [menuItems, setMenuItems] = useState<any[]>(baseMenu);
  const pathname = usePathname();

  useEffect(() => {
    async function loadProfile() {
      const res = await getProfileAction();
      if (res.success) {
        setUser(res.user);
        
        const dynamicMenu = [...baseMenu];
        
        if (res.user.role === "super_admin" || parseInt(res.user.role_level) >= 90) {
          // Super Admin için Kategori Bazlı Menüler
          dynamicMenu.push(
            {
              title: "YÖNETİM",
              links: [
                { href: "/dashboard/admin/kullanicilar", label: "Kullanıcı Yönetimi", icon: UserCircle, match: "kullanicilar" },
                { href: "/dashboard/admin/byk", label: "BYK Yönetimi", icon: Building, match: "byk" },
                { href: "/dashboard/admin/alt-birimler", label: "Alt Birimler", icon: Sitemap, match: "alt-birimler" },
                { href: "/dashboard/admin/yetkiler", label: "Üye Yetkileri", icon: Sliders, match: "yetkiler" },
              ]
            },
            {
              title: "İŞLEMLER",
              links: [
                { href: "/dashboard/admin/izin-talepleri", label: "İzin Talepleri", icon: Calendar, match: "izin-talepleri" },
                { href: "/dashboard/admin/harcama-talepleri", label: "Harcama Talepleri", icon: ClipboardList, match: "harcama-talepleri" },
                { href: "/dashboard/admin/demirbaslar", label: "Demirbaş Yönetimi", icon: Box, match: "demirbaslar" },
              ]
            },
            {
              title: "RAPORLAR & AYARLAR",
              links: [
                { href: "/dashboard/admin/raporlar", label: "Raporlar & Analiz", icon: FilePieChart, match: "raporlar" },
                { href: "/dashboard/admin/ayarlar", label: "Sistem Ayarları", icon: ShieldCheck, match: "ayarlar" },
                { href: "/dashboard/admin/email-sablonlari", label: "E-posta Şablonları", icon: MailOpen, match: "email-sablonlari" },
              ]
            }
          );
        }
        setMenuItems(dynamicMenu);
      } else {
        window.location.href = "/";
      }
    }
    loadProfile();
  }, []);

  const handleLogout = async () => {
    await logoutAction();
    window.location.href = "/";
  };

  return (
    <div className="min-h-screen bg-zinc-950 text-zinc-100 flex overflow-hidden font-sans">
      {/* Mobile Backdrop */}
      <AnimatePresence>
        {mobileOpen && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={() => setMobileOpen(false)}
            className="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 lg:hidden"
          />
        )}
      </AnimatePresence>

      {/* Sidebar */}
      <aside
        className={`fixed inset-y-0 left-0 z-50 flex flex-col bg-zinc-900 border-r border-white/5 transition-all duration-300 transform 
          ${sidebarOpen ? "w-64" : "w-20"} 
          ${mobileOpen ? "translate-x-0" : "-translate-x-full lg:translate-x-0"}
        `}
      >
        {/* Sidebar Header */}
        <div className="h-16 flex items-center justify-between px-4 border-b border-white/5">
          <div className="flex items-center gap-3 overflow-hidden">
            <div className="w-8 h-8 bg-emerald-600 rounded-lg flex items-center justify-center shrink-0 shadow-md shadow-emerald-600/20 font-bold text-white">
              A
            </div>
            {sidebarOpen && (
              <motion.span 
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                className="font-bold text-lg tracking-wide text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-400"
              >
                AİFNET
              </motion.span>
            )}
          </div>
          <button 
            onClick={() => setSidebarOpen(!sidebarOpen)}
            className="hidden lg:flex text-zinc-400 hover:text-white p-1.5 rounded-lg hover:bg-white/5 transition-colors"
          >
            <Menu className="w-5 h-5" />
          </button>
        </div>

        {/* Sidebar Navigation */}
        <nav className="flex-1 px-3 py-4 overflow-y-auto space-y-4">
          {menuItems.map((section, sIndex) => (
            <div key={sIndex} className="space-y-1">
              {sidebarOpen && section.title && (
                <div className="px-3 text-[10px] font-bold text-zinc-600 tracking-wider mb-2">
                  {section.title}
                </div>
              )}
              {section.links.map((item: any) => {
                const isActive = pathname === item.href || (item.match !== "/dashboard" && pathname.includes(item.match));
                const Icon = item.icon;

                return (
                  <Link
                    key={item.href}
                    href={item.href}
                    className={`flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group relative
                      ${isActive 
                        ? "bg-emerald-600 text-white shadow-lg shadow-emerald-600/10" 
                        : "text-zinc-400 hover:text-white hover:bg-white/5"
                      }
                    `}
                  >
                    <Icon className={`w-5 h-5 shrink-0 ${isActive ? "text-white" : "text-zinc-500 group-hover:text-emerald-400 transition-colors"}`} />
                    {sidebarOpen && (
                      <motion.span initial={{ opacity: 0 }} animate={{ opacity: 1 }}>
                        {item.label}
                      </motion.span>
                    )}
                    {!sidebarOpen && (
                      <div className="absolute left-14 invisible group-hover:visible opacity-0 group-hover:opacity-100 bg-zinc-800 border border-white/10 px-2.5 py-1.5 rounded-md text-xs font-semibold whitespace-nowrap shadow-xl z-50 transition-all">
                        {item.label}
                      </div>
                    )}
                  </Link>
                );
              })}
            </div>
          ))}
        </nav>

        {/* Sidebar Footer */}
        <div className="p-3 border-t border-white/5 space-y-1">
          <Link href="/dashboard/ayarlar" className="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-zinc-400 hover:text-white hover:bg-white/5 group">
            <Settings className="w-5 h-5 shrink-0 text-zinc-500 group-hover:text-emerald-400" />
            {sidebarOpen && <span>Ayarlar</span>}
          </Link>
          <button onClick={handleLogout} className="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-red-400 hover:text-red-300 hover:bg-red-500/5 group">
            <LogOut className="w-5 h-5 shrink-0 text-red-500/70 group-hover:text-red-400" />
            {sidebarOpen && <span>Çıkış Yap</span>}
          </button>
        </div>
      </aside>

      {/* Main Content Pane */}
      <div className={`flex-1 flex flex-col transition-all duration-300 ${sidebarOpen ? "lg:ml-64" : "lg:ml-20"}`}>
        {/* Navbar */}
        <header className="h-16 flex items-center justify-between px-6 bg-zinc-950/50 backdrop-blur-xl border-b border-white/5 sticky top-0 z-30">
          <div className="flex items-center gap-3">
            <button 
              onClick={() => setMobileOpen(!mobileOpen)} 
              className="p-2 lg:hidden text-zinc-400 hover:text-white rounded-lg hover:bg-white/5"
            >
              <Menu className="w-5 h-5" />
            </button>
            <div className="text-zinc-400 text-sm font-medium hidden md:block">
              Yönetim Paneli / <span className="text-zinc-200">Kontrol Paneli</span>
            </div>
          </div>

          <div className="flex items-center gap-4">
            {/* Notification */}
            <button className="relative p-2 text-zinc-400 hover:text-white hover:bg-white/5 rounded-xl transition-all">
              <Bell className="w-5 h-5" />
              <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-emerald-500 rounded-full shadow-lg shadow-emerald-500/20" />
            </button>
            
            {/* User Profile */}
            <button className="flex items-center gap-2 p-1 pl-3 rounded-xl border border-white/5 bg-zinc-900 hover:bg-zinc-800 hover:border-white/10 transition-all">
              <div className="flex flex-col items-end text-right">
                <span className="text-xs font-semibold text-zinc-200">{user ? user.name : "Yükleniyor..."}</span>
                <span className="text-[10px] font-medium text-zinc-500 capitalize">{user ? user.role : "-"}</span>
              </div>
              <div className="w-8 h-8 rounded-lg bg-zinc-800 flex items-center justify-center text-emerald-400 border border-white/10">
                <UserCircle className="w-5 h-5" />
              </div>
            </button>
          </div>
        </header>

        {/* Contents */}
        <main className="flex-1 p-6 overflow-x-hidden">
          {children}
        </main>
      </div>
    </div>
  );
}
