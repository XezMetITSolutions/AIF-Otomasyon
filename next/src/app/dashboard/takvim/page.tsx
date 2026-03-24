"use client";

import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Calendar as CalendarIcon, MapPin, Clock, AlignLeft, CalendarCheck2, ChevronLeft, ChevronRight, X } from "lucide-react";
import { getEtkinliklerAction } from "../../actions/auth";

export default function TakvimPage() {
  const [etkinlikler, setEtkinlikler] = useState<any[]>([]);
  const [filters, setFilters] = useState<string[]>([]);
  const [canManage, setCanManage] = useState(false);
  const [loading, setLoading] = useState(true);

  const [birimFilter, setBirimFilter] = useState("");
  const [currentDate, setCurrentDate] = useState(new Date());
  const [selectedDayEvents, setSelectedDayEvents] = useState<any[] | null>(null);

  useEffect(() => {
    loadEtkinlikler();
  }, [currentDate, birimFilter]);

  async function loadEtkinlikler() {
    setLoading(true);
    const month = (currentDate.getMonth() + 1).toString();
    const year = currentDate.getFullYear().toString();
    
    const res = await getEtkinliklerAction({ ay: month, yil: year, birim: birimFilter });
    if (res.success) {
      setEtkinlikler(res.etkinlikler || []);
      setFilters(res.filters || []);
      setCanManage(res.canManage || false);
    }
    setLoading(false);
  }

  const handlePrevMonth = () => {
    setCurrentDate(new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 1));
  };

  const handleNextMonth = () => {
    setCurrentDate(new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 1));
  };

  // --- Takvim Hücreleri Matrisi ---
  const generateCalendarDays = () => {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    const firstDayIndex = new Date(year, month, 1).getDay(); // 0 (Paz) - 6 (Cmt)
    // Pazartesi'den başlatmak için kaydır
    const startDay = firstDayIndex === 0 ? 6 : firstDayIndex - 1;

    const totalDays = new Date(year, month + 1, 0).getDate();
    const prevMonthDays = new Date(year, month, 0).getDate();

    const days = [];

    // Önceki aydan kalan günler
    for (let i = startDay - 1; i >= 0; i--) {
      days.push({ day: prevMonthDays - i, currentMonth: false, date: new Date(year, month - 1, prevMonthDays - i) });
    }

    // Bu ayın günleri
    for (let i = 1; i <= totalDays; i++) {
      days.push({ day: i, currentMonth: true, date: new Date(year, month, i) });
    }

    // Gelecek aydan günler (42 hücreye tamamla)
    const remaining = 42 - days.length;
    for (let i = 1; i <= remaining; i++) {
        days.push({ day: i, currentMonth: false, date: new Date(year, month + 1, i) });
    }

    return days;
  };

  const calendarDays = generateCalendarDays();

  const getDayEvents = (dayDate: Date) => {
     return etkinlikler.filter(e => {
        const evDate = new Date(e.baslangic_tarihi);
        return evDate.getDate() === dayDate.getDate() && 
               evDate.getMonth() === dayDate.getMonth() && 
               evDate.getFullYear() === dayDate.getFullYear();
     });
  };

  const formatEventTime = (baslangicStr: string, bitisStr: string) => {
    const s = new Date(baslangicStr);
    const e = new Date(bitisStr);
    const timeS = s.toLocaleTimeString("tr-TR", { hour: "2-digit", minute: "2-digit" });
    const timeE = e.toLocaleTimeString("tr-TR", { hour: "2-digit", minute: "2-digit" });
    return (timeS === "00:00" && timeE === "23:59") ? "Tüm Gün" : `${timeS} - ${timeE}`;
  };

  return (
    <div className="space-y-6">
      {/* BAŞLIK VE AY DEĞİŞTİRME */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
            <CalendarCheck2 className="w-6 h-6 text-emerald-400" /> Çalışma Takvimi
          </h1>
          <p className="text-zinc-500 text-sm mt-1">Görsel ay ve hücre bazlı matris görünümü.</p>
        </div>

        <div className="flex items-center gap-3 bg-zinc-900 border border-white/5 rounded-xl px-4 py-2">
           <button onClick={handlePrevMonth} className="p-1 hover:bg-white/5 rounded-lg text-zinc-400 hover:text-white transition-all"><ChevronLeft className="w-5 h-5" /></button>
           <span className="text-sm font-bold text-zinc-200 min-w-[120px] text-center">
              {currentDate.toLocaleString('tr-TR', { month: 'long', year: 'numeric' })}
           </span>
           <button onClick={handleNextMonth} className="p-1 hover:bg-white/5 rounded-lg text-zinc-400 hover:text-white transition-all"><ChevronRight className="w-5 h-5" /></button>
        </div>
      </div>

      {/* FİLTRELER (Birim bazlı) */}
      {filters.length > 0 && (
         <div className="flex bg-zinc-900/50 rounded-xl p-1 border border-white/5 w-full md:w-auto overflow-x-auto hide-scrollbar">
            <button 
              onClick={() => setBirimFilter("")}
              className={`px-4 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-all ${birimFilter === "" ? "bg-emerald-500/10 text-emerald-400 border border-emerald-500/20" : "text-zinc-400 hover:text-white"}`}
            >
              Tümü
            </button>
            {filters.map(f => (
              <button 
                key={f}
                onClick={() => setBirimFilter(f)}
                className={`px-4 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-all flex items-center gap-1 ${birimFilter === f ? "bg-emerald-500/10 text-emerald-400 border border-emerald-500/20" : "text-zinc-400 hover:text-white"}`}
              >
                {f}
              </button>
            ))}
         </div>
      )}

      {/* TAKVİM IZGARASI / MESH GRID */}
      <div className="bg-zinc-900 border border-white/5 rounded-2xl overflow-hidden shadow-xl">
         {/* Haftanın Günleri */}
         <div className="grid grid-cols-7 border-b border-white/5 bg-zinc-950/40 text-center py-3">
            {['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'].map((day, i) => (
               <div key={day} className={`text-xs font-bold ${i >= 5 ? 'text-zinc-500' : 'text-zinc-400'}`}>{day}</div>
            ))}
         </div>

         {/* Hücreler */}
         {loading ? (
             <div className="flex justify-center items-center py-20 text-emerald-500 font-bold">Yükleniyor...</div>
         ) : (
            <div className="grid grid-cols-7">
               {calendarDays.map((c, idx) => {
                  const dayEvents = getDayEvents(c.date);
                  const isCurrent = c.currentMonth;
                  const isToday = new Date().toDateString() === c.date.toDateString();

                  return (
                     <div 
                       key={idx} 
                       onClick={() => dayEvents.length > 0 && setSelectedDayEvents(dayEvents)}
                       className={`border-r border-b border-white/5 h-24 p-2 relative flex flex-col items-start justify-start transition-all cursor-pointer ${
                          !isCurrent ? 'opacity-30 bg-zinc-950/20' : 'hover:bg-white/[0.02]'
                       } ${isToday ? 'bg-emerald-500/[0.03]' : ''}`}
                     >
                        <span className={`text-xs font-bold ${isToday ? 'text-emerald-500' : isCurrent ? 'text-zinc-400' : 'text-zinc-600'}`}>
                           {c.day}
                        </span>

                        <div className="mt-1 w-full space-y-1 overflow-hidden h-full flex flex-col">
                           {dayEvents.slice(0, 2).map((e, index) => (
                              <div key={index} className="px-1.5 py-0.5 rounded text-[9px] font-bold truncate tracking-tight text-white flex items-center gap-1" style={{ backgroundColor: `${e.byk_renk || '#10b981'}30`, border: `1px solid ${e.byk_renk || '#10b981'}50`, color: e.byk_renk || '#10b981' }}>
                                 {e.baslik}
                              </div>
                           ))}
                           {dayEvents.length > 2 && (
                              <span className="text-[9px] text-zinc-500 font-bold pl-1">+{dayEvents.length - 2} etkinlik daha</span>
                           )}
                        </div>
                     </div>
                  );
               })}
            </div>
         )}
      </div>

      {/* DETAY POPUP / MODAL */}
      <AnimatePresence>
         {selectedDayEvents && (
            <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} onClick={() => setSelectedDayEvents(null)} className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
               <motion.div initial={{ scale: 0.95 }} animate={{ scale: 1 }} exit={{ scale: 0.95 }} onClick={(e) => e.stopPropagation()} className="bg-zinc-900 border border-white/5 rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4">
                  <div className="flex justify-between items-center border-b border-white/5 pb-3">
                     <h3 className="font-bold text-white text-base">Planlanmış Etkinlikler</h3>
                     <button onClick={() => setSelectedDayEvents(null)} className="p-1 hover:bg-white/5 rounded-lg text-zinc-500 hover:text-white"><X className="w-4 h-4" /></button>
                  </div>

                  <div className="space-y-3 max-h-[350px] overflow-y-auto pr-1">
                     {selectedDayEvents.map(e => (
                        <div key={e.etkinlik_id} className="bg-zinc-950 p-4 rounded-xl border border-white/5 relative">
                           <div className="flex items-center gap-1.5 mb-1.5">
                              <div className="w-2 h-2 rounded-full" style={{ backgroundColor: e.byk_renk || '#10b981' }}></div>
                              <span className="text-[10px] font-bold text-zinc-500 uppercase">{e.byk_kodu || e.byk_adi}</span>
                           </div>
                           <h4 className="font-bold text-zinc-100 text-sm leading-snug">{e.baslik}</h4>
                           <div className="space-y-1 mt-2">
                              <div className="flex items-center gap-2 text-xs text-zinc-400">
                                 <Clock className="w-3.5 h-3.5 text-zinc-500" />
                                 {formatEventTime(e.baslangic_tarihi, e.bitis_tarihi)}
                              </div>
                              {e.konum && (
                                 <div className="flex items-center gap-2 text-xs text-zinc-400">
                                    <MapPin className="w-3.5 h-3.5 text-zinc-500" />
                                    {e.konum}
                                 </div>
                              )}
                           </div>
                           {e.aciklama && (
                              <p className="mt-2 text-xs text-zinc-500 border-t border-white/5 pt-2 flex gap-1 items-start">
                                 <AlignLeft className="w-3.5 h-3.5 text-zinc-600 mt-0.5" />
                                 {e.aciklama.replace(/<[^>]*>?/gm, '')}
                              </p>
                           )}
                        </div>
                     ))}
                  </div>
               </motion.div>
            </motion.div>
         )}
      </AnimatePresence>
    </div>
  );
}
