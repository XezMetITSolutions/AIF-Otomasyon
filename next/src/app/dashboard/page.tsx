"use client";

import { motion } from "framer-motion";
import { useState, useEffect } from "react";
import { getDashboardStats } from "../actions/auth";
import { 
  Calendar, Users, Bell, FileText, 
  ArrowUpRight, Clock, CheckCircle2, AlertCircle 
} from "lucide-react";

export default function DashboardPage() {
  const [stats, setStats] = useState<any[]>([]);
  const [meetings, setMeetings] = useState<any[]>([]);
  const [announcements, setAnnouncements] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function load() {
      const res = await getDashboardStats();
      if (res.success) {
        setStats([
          { label: "Yaklaşan Toplantı", value: res.stats.upcoming_meetings.toString(), icon: Users, class: "bg-emerald-500/10 text-emerald-500 border-emerald-500/20" },
          { label: "Aktif Duyuru", value: res.stats.active_announcements.toString(), icon: Bell, class: "bg-blue-500/10 text-blue-500 border-blue-500/20" },
          { label: "Bekleyen İzin", value: res.stats.pending_leaves.toString(), icon: Clock, class: "bg-amber-500/10 text-amber-500 border-amber-500/20" },
          { label: "Aylık Harcama", value: `€${res.stats.expenses}`, icon: FileText, class: "bg-red-500/10 text-red-500 border-red-500/20" },
        ]);
        setMeetings(res.meetings);
        setAnnouncements(res.announcements);
      }
      setLoading(false);
    }
    load();
  }, []);

  const formatDate = (dateString: string) => {
    if (!dateString) return { day: "-", month: "-", time: "-" };
    const d = new Date(dateString);
    return {
      day: d.getDate(),
      month: d.toLocaleString('tr-TR', { month: 'short' }),
      time: d.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' })
    };
  };

  if (loading) return <div className="min-h-[400px] flex items-center justify-center text-emerald-500 font-bold">Veriler Yükleniyor...</div>;

  return (
    <div className="space-y-6">
      {/* Welcome header */}
      <div>
        <h1 className="text-2xl font-bold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-white via-zinc-200 to-zinc-400">
          Hoş Geldiniz, <span className="text-emerald-400">Kullanıcı</span>
        </h1>
        <p className="text-zinc-500 text-sm mt-1">AİFNET Yönetim Paneline hoş geldiniz. İşte bugünkü özetiniz.</p>
      </div>

      {/* Stats Cards Section */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {stats.map((stat, index) => {
          const Icon = stat.icon;
          if (loading) return <div className="min-h-[400px] flex items-center justify-center text-emerald-500 font-bold">Veriler Yükleniyor...</div>;

  return (
            <motion.div
              key={index}
              initial={{ opacity: 0, y: 15 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: index * 0.1, duration: 0.4 }}
              className="p-5 bg-zinc-900 border border-white/5 rounded-2xl flex items-center justify-between hover:border-white/10 hover:bg-zinc-800/80 transition-all cursor-pointer group"
            >
              <div className="space-y-1">
                <span className="text-xs font-semibold text-zinc-500 tracking-wider">
                  {stat.label}
                </span>
                <div className="text-2xl font-bold text-zinc-100 group-hover:text-emerald-400 transition-colors">
                  {stat.value}
                </div>
              </div>
              <div className={`p-3 rounded-xl border ${stat.class}`}>
                <Icon className="w-5 h-5" />
              </div>
            </motion.div>
          );
        })}
      </div>

      {/* Grid Content Panels */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Upcoming Meetings Panel */}
        <div className="bg-zinc-900 border border-white/5 rounded-2xl overflow-hidden">
          <div className="p-5 border-b border-white/5 flex items-center justify-between">
            <div className="flex items-center gap-2">
              <Users className="w-5 h-5 text-emerald-500" />
              <h2 className="font-bold text-zinc-200">Yaklaşan Toplantılar</h2>
            </div>
            <button className="text-xs font-semibold text-emerald-500 hover:text-emerald-400 flex items-center gap-1">
              Tümü <ArrowUpRight className="w-4 h-4" />
            </button>
          </div>
          <div className="p-2 divide-y divide-white/5">
            {meetings.map((meeting, index) => (
              <div key={index} className="p-3 flex items-center justify-between hover:bg-white/5 rounded-xl transition-all cursor-pointer">
                <div className="flex items-center gap-3">
                  <div className="bg-zinc-800 p-2.5 rounded-xl text-center min-w-[50px] border border-white/5">
                    <span className="text-xs font-bold text-zinc-200 block">{meeting.date.split('.')[0]}</span>
                    <span className="text-[10px] text-zinc-500 font-medium">{meeting.date.split('.')[1]} ay</span>
                  </div>
                  <div>
                    <h3 className="text-sm font-semibold text-zinc-300">{meeting.title}</h3>
                    <span className="text-xs text-zinc-500 flex items-center gap-1 mt-0.5">
                      <Clock className="w-3 h-3" /> {meeting.time} <span className="text-zinc-700">|</span> {meeting.type}
                    </span>
                  </div>
                </div>
                <div>
                  <span className={`text-xs px-2.5 py-1 rounded-full font-medium ${
                    meeting.status === "katilacak" 
                      ? "bg-emerald-500/10 text-emerald-500 border border-emerald-500/20" 
                      : "bg-amber-500/10 text-amber-500 border border-amber-500/20"
                  }`}>
                    {meeting.status === "katilacak" ? "Katılacak" : "Beklemede"}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Announcements Panel */}
        <div className="bg-zinc-900 border border-white/5 rounded-2xl overflow-hidden">
          <div className="p-5 border-b border-white/5 flex items-center justify-between">
            <div className="flex items-center gap-2">
              <Bell className="w-5 h-5 text-blue-500" />
              <h2 className="font-bold text-zinc-200">Son Duyurular</h2>
            </div>
          </div>
          <div className="p-4 space-y-4">
            {announcements.length === 0 ? (
              <div className="p-4 text-center text-zinc-500 text-sm">Yeni duyuru yok.</div>
            ) : announcements.map((item, index) => {
              const dParts = formatDate(item.olusturma_tarihi);
              if (loading) return <div className="min-h-[400px] flex items-center justify-center text-emerald-500 font-bold">Veriler Yükleniyor...</div>;

  return (
                <div key={index} className="p-4 bg-zinc-800/50 border border-white/5 rounded-xl space-y-2 hover:border-white/10 hover:bg-zinc-800 transition-all cursor-pointer">
                  <div className="flex justify-between items-center">
                    <h3 className="text-sm font-bold text-zinc-200">{item.baslik}</h3>
                    <span className="text-[11px] text-zinc-600">{dParts.day} {dParts.month}</span>
                  </div>
                  <p className="text-xs text-zinc-400 line-clamp-2 leading-relaxed" dangerouslySetInnerHTML={{ __html: item.icerik }}>
                  </p>
                </div>
              );
            })}
          </div>
        </div>
      </div>
    </div>
  );
}
