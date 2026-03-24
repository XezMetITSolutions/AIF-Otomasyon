"use client";

import { motion, AnimatePresence } from "framer-motion";
import { useState, useEffect } from "react";
import { getDashboardStats } from "../actions/auth";
import { 
  Calendar, Users, Bell, FileText, 
  ArrowUpRight, Clock, Sliders, X, Check
} from "lucide-react";

export default function DashboardPage() {
  const [stats, setStats] = useState<any[]>([]);
  const [meetings, setMeetings] = useState<any[]>([]);
  const [announcements, setAnnouncements] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  // Widget settings
  const [configModalOpen, setConfigModalOpen] = useState(false);
  const [visibleWidgets, setVisibleWidgets] = useState({
     stats: true,
     meetings: true,
     announcements: true
  });

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
        setMeetings(res.meetings || []);
        setAnnouncements(res.announcements || []);
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
    <div className="space-y-6 relative">
      {/* Welcome header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-white via-zinc-200 to-zinc-400">
            Hoş Geldiniz, <span className="text-emerald-400">Kullanıcı</span>
          </h1>
          <p className="text-zinc-500 text-sm mt-1">AİFNET Yönetim Paneline hoş geldiniz. İşte bugünkü özetiniz.</p>
        </div>
        <div>
           <button onClick={() => setConfigModalOpen(true)} className="bg-zinc-900 border border-white/5 hover:border-white/10 text-zinc-300 font-medium px-4 py-2 rounded-xl text-xs flex items-center gap-2 shadow-lg transition-all animate-pulse hover:animate-none">
              <Sliders className="w-4 h-4 text-emerald-400" /> Özelleştir
           </button>
        </div>
      </div>

      {/* Stats Cards Section */}
      {visibleWidgets.stats && (
         <motion.div initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
           {stats.map((stat, index) => {
             const Icon = stat.icon;
             return (
               <motion.div
                 key={index}
                 className="p-5 bg-zinc-900 border border-white/5 rounded-2xl flex items-center justify-between hover:border-white/10 hover:bg-zinc-800/80 transition-all cursor-pointer group"
               >
                 <div className="space-y-1">
                   <span className="text-xs font-semibold text-zinc-500 tracking-wider">{stat.label}</span>
                   <div className="text-2xl font-bold text-zinc-100 group-hover:text-emerald-400 transition-colors">{stat.value}</div>
                 </div>
                 <div className={`p-3 rounded-xl border ${stat.class}`}><Icon className="w-5 h-5" /></div>
               </motion.div>
             );
           })}
         </motion.div>
      )}

      {/* Grid Content Panels */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Upcoming Meetings Panel */}
        {visibleWidgets.meetings && (
           <motion.div initial={{ opacity: 0, y: 15 }} animate={{ opacity: 1, y: 0 }} className="bg-zinc-900 border border-white/5 rounded-2xl overflow-hidden">
             <div className="p-5 border-b border-white/5 flex items-center justify-between">
               <div className="flex items-center gap-2">
                 <Users className="w-5 h-5 text-emerald-500" />
                 <h2 className="font-bold text-zinc-200">Yaklaşan Toplantılar</h2>
               </div>
               <button className="text-xs font-semibold text-emerald-500 hover:text-emerald-400 flex items-center gap-1">Tümü <ArrowUpRight className="w-4 h-4" /></button>
             </div>
             <div className="p-2 divide-y divide-white/5">
               {meetings.length === 0 ? <div className="p-4 text-center text-zinc-500 text-sm">Toplantı bulunmuyor.</div> : meetings.map((meeting, index) => (
                 <div key={index} className="p-3 flex items-center justify-between hover:bg-white/5 rounded-xl transition-all cursor-pointer">
                   <div className="flex items-center gap-3">
                     <div className="bg-zinc-800 p-2.5 rounded-xl text-center min-w-[50px] border border-white/5">
                       <span className="text-xs font-bold text-zinc-200 block">{meeting.date?.split('.')?.[0] || "-"}</span>
                       <span className="text-[10px] text-zinc-500 font-medium">{meeting.date?.split('.')?.[1] || "-"} ay</span>
                     </div>
                     <div>
                       <h3 className="text-sm font-semibold text-zinc-300">{meeting.title}</h3>
                       <span className="text-xs text-zinc-500 flex items-center gap-1 mt-0.5"><Clock className="w-3 h-3" /> {meeting.time} <span className="text-zinc-700">|</span> {meeting.type}</span>
                     </div>
                   </div>
                   <div>
                     <span className={`text-xs px-2.5 py-1 rounded-full font-medium ${meeting.status === "katilacak" ? "bg-emerald-500/10 text-emerald-500 border border-emerald-500/20" : "bg-zinc-800 text-zinc-500"}`}>
                       {meeting.status === "katilacak" ? "Katılacak" : "Beklemede"}
                     </span>
                   </div>
                 </div>
               ))}
             </div>
           </motion.div>
        )}

        {/* Announcements Panel */}
        {visibleWidgets.announcements && (
           <motion.div initial={{ opacity: 0, y: 15 }} animate={{ opacity: 1, y: 0 }} className="bg-zinc-900 border border-white/5 rounded-2xl overflow-hidden">
             <div className="p-5 border-b border-white/5 flex items-center justify-between">
               <div className="flex items-center gap-2">
                 <Bell className="w-5 h-5 text-blue-500" />
                 <h2 className="font-bold text-zinc-200">Son Duyurular</h2>
               </div>
             </div>
             <div className="p-4 space-y-4">
               {announcements.length === 0 ? <div className="p-4 text-center text-zinc-500 text-sm">Yeni duyuru yok.</div> : announcements.map((item, index) => {
                 const dParts = formatDate(item.olusturma_tarihi);
                 return (
                   <div key={index} className="p-4 bg-zinc-800/50 border border-white/5 rounded-xl space-y-2 hover:border-white/10 hover:bg-zinc-800 transition-all cursor-pointer">
                     <div className="flex justify-between items-center">
                       <h3 className="text-sm font-bold text-zinc-200">{item.baslik}</h3>
                       <span className="text-[11px] text-zinc-600">{dParts.day} {dParts.month}</span>
                     </div>
                     <p className="text-xs text-zinc-400 line-clamp-2 leading-relaxed" dangerouslySetInnerHTML={{ __html: item.icerik }}></p>
                   </div>
                 );
               })}
             </div>
           </motion.div>
        )}
      </div>

      {/* CUSTOMIZE MODAL / SCREEN */}
      <AnimatePresence>
        {configModalOpen && (
           <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" onClick={() => setConfigModalOpen(false)}>
              <motion.div 
                 initial={{ opacity: 0, scale: 0.95 }} 
                 animate={{ opacity: 1, scale: 1 }} 
                 exit={{ opacity: 0, scale: 0.95 }} 
                 onClick={(e: any) => e.stopPropagation()}
                 className="bg-zinc-900 border border-white/10 rounded-2xl p-6 w-full max-w-sm shadow-2xl relative"
              >
                  <button onClick={() => setConfigModalOpen(false)} className="absolute top-4 right-4 text-zinc-500 hover:text-white"><X className="w-4 h-4" /></button>
                  <h3 className="text-base font-bold text-zinc-100 flex items-center gap-2 mb-1"><Sliders className="w-4 h-4 text-emerald-400" /> Paneli Özelleştir</h3>
                  <p className="text-xs text-zinc-500 mb-4 border-b border-white/5 pb-2">Ana sayfa görünümünü kendi tercihinize göre dizayn edin.</p>

                  <div className="space-y-3">
                     {[
                        { id: 'stats', label: 'Özet Sayaç Kartları' },
                        { id: 'meetings', label: 'Yaklaşan Toplantılar Bölümü' },
                        { id: 'announcements', label: 'Son Duyurular Paneli' }
                     ].map(item => (
                        <div key={item.id} className="flex items-center justify-between bg-zinc-950 p-3 rounded-xl border border-white/5">
                           <span className="text-xs font-semibold text-zinc-300">{item.label}</span>
                           <button 
                             onClick={() => setVisibleWidgets(prev => ({ ...prev, [item.id]: !((prev as any)[item.id]) }))} 
                             className={`w-9 h-5 rounded-full flex items-center transition-all ${((visibleWidgets as any)[item.id]) ? 'bg-emerald-600' : 'bg-zinc-800'}`}
                           >
                              <div className={`w-3.5 h-3.5 bg-white rounded-full shadow-md transform transition-all ${((visibleWidgets as any)[item.id]) ? 'translate-x-4' : 'translate-x-1'}`} />
                           </button>
                        </div>
                     ))}
                  </div>

                  <button onClick={() => setConfigModalOpen(false)} className="w-full mt-5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold py-2.5 rounded-xl transition-all">
                     Tercihleri Kaydet
                  </button>
              </motion.div>
           </div>
        )}
      </AnimatePresence>
    </div>
  );
}
