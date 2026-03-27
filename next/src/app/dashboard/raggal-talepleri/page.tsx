"use client";

import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Calendar, Send, CheckCircle2, XCircle, AlertCircle, Trash2 } from "lucide-react";

export default function RaggalTalepleriPage() {
  const [activeTab, setActiveTab] = useState<"takvim" | "talep" | "onay">("takvim");
  const [requests, setRequests] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState<{ text: string; type: "success" | "error" } | null>(null);

  // Calendar specific state
  const [currentDate, setCurrentDate] = useState(new Date());

  useEffect(() => {
    loadRequests();
  }, [activeTab]);

  async function loadRequests() {
    setLoading(true);
    try {
      const res = await fetch(`/api/raggal-talepleri.php?tab=${activeTab}`);
      const data = await res.json();
      if (data.success) {
        setRequests(data.requests || []);
      }
    } catch (e) {
      setMessage({ text: "Veriler yüklenirken hata oluştu.", type: "error" });
    }
    setLoading(false);
  }

  // Calendar Helpers
  const daysInMonth = (year: number, month: number) => new Date(year, month + 1, 0).getDate();
  const firstDayOfMonth = (year: number, month: number) => new Date(year, month, 1).getDay(); // 0 is Sunday
  
  const getCalendarDays = () => {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const totalDays = daysInMonth(year, month);
    // Find first Monday (Turkish culture starts week on Monday)
    let startOffset = firstDayOfMonth(year, month);
    startOffset = (startOffset === 0) ? 6 : startOffset - 1; // Adjust for Monday start

    const days = [];
    const prevMonthTotalDays = daysInMonth(year, month - 1);
    
    // Fill previous month padding
    for (let i = startOffset - 1; i >= 0; i--) {
        days.push({ day: prevMonthTotalDays - i, month: month - 1, year, padding: true });
    }
    // Current month days
    for (let i = 1; i <= totalDays; i++) {
        days.push({ day: i, month: month, year, padding: false });
    }
    // Future month padding
    const remaining = 42 - days.length; // Ensure 6 weeks grid
    for (let i = 1; i <= remaining; i++) {
        days.push({ day: i, month: month + 1, year, padding: true });
    }
    return days;
  };

  const nextMonth = () => setCurrentDate(new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 1));
  const prevMonth = () => setCurrentDate(new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 1));
  
  const monthNames = ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"];
  const calendarDays = getCalendarDays();

  const getEventsForDay = (d: number, m: number, y: number) => {
    return requests.filter(r => {
        const start = new Date(r.baslangic_tarihi);
        const end = new Date(r.bitis_tarihi);
        const current = new Date(y, m, d);
        // Compare dates (ignoring time)
        const sTime = new Date(start.getFullYear(), start.getMonth(), start.getDate()).getTime();
        const eTime = new Date(end.getFullYear(), end.getMonth(), end.getDate()).getTime();
        const cTime = current.getTime();
        return cTime >= sTime && cTime <= eTime;
    });
  };

  return (
    <div className="p-6 space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-zinc-100 flex items-center gap-2">
            <Calendar className="w-6 h-6 text-emerald-500" />
            Raggal Talepleri
          </h1>
          <p className="text-sm text-zinc-400">Takvim görünümü ve rezervasyon yönetimi.</p>
        </div>
      </div>

      <div className="flex gap-2 border-b border-white/5 pb-2">
        <button
          onClick={() => setActiveTab("takvim")}
          className={`px-4 py-2 rounded-lg text-sm font-medium transition-all ${
            activeTab === "takvim" ? "bg-emerald-600 text-white" : "text-zinc-400 hover:text-white hover:bg-white/5"
          }`}
        >
          Güncel Takvim
        </button>
        <button
          onClick={() => setActiveTab("talep")}
          className={`px-4 py-2 rounded-lg text-sm font-medium transition-all ${
            activeTab === "talep" ? "bg-emerald-600 text-white" : "text-zinc-400 hover:text-white hover:bg-white/5"
          }`}
        >
          Taleplerim
        </button>
        <button
          onClick={() => setActiveTab("onay")}
          className={`px-4 py-2 rounded-lg text-sm font-medium transition-all ${
            activeTab === "onay" ? "bg-emerald-600 text-white" : "text-zinc-400 hover:text-white hover:bg-white/5"
          }`}
        >
          Onay Bekleyenler
        </button>
      </div>

      <AnimatePresence mode="wait">
        {activeTab === "takvim" ? (
          <motion.div 
            key="takvim"
            initial={{ opacity: 0, scale: 0.98 }}
            animate={{ opacity: 1, scale: 1 }}
            exit={{ opacity: 0, scale: 0.98 }}
            className="space-y-4"
          >
            <div className="flex items-center justify-between bg-zinc-900/50 p-4 rounded-2xl border border-white/5 shadow-xl">
               <button onClick={prevMonth} className="p-2 hover:bg-white/5 rounded-full text-zinc-400 transition-colors">&larr;</button>
               <h2 className="text-lg font-bold text-white uppercase tracking-wider">{monthNames[currentDate.getMonth()]} {currentDate.getFullYear()}</h2>
               <button onClick={nextMonth} className="p-2 hover:bg-white/5 rounded-full text-zinc-400 transition-colors">&rarr;</button>
            </div>

            <div className="bg-zinc-900/50 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
               <div className="grid grid-cols-7 border-b border-white/5 bg-zinc-900/80">
                  {["Pzt", "Sal", "Çar", "Per", "Cum", "Cmt", "Paz"].map(d => (
                    <div key={d} className="p-3 text-center text-xs font-bold text-emerald-500 uppercase">{d}</div>
                  ))}
               </div>
               <div className="grid grid-cols-7">
                  {calendarDays.map((dateObj, idx) => {
                    const dayEvents = dateObj.padding ? [] : getEventsForDay(dateObj.day, dateObj.month, dateObj.year);
                    const isToday = !dateObj.padding && dateObj.day === new Date().getDate() && dateObj.month === new Date().getMonth() && dateObj.year === new Date().getFullYear();

                    return (
                        <div key={idx} className={`min-h-[100px] border-r border-b border-white/5 p-2 transition-colors relative ${dateObj.padding ? "opacity-20" : "hover:bg-white/5"} ${isToday ? "bg-emerald-500/[0.03]" : ""}`}>
                            <span className={`text-xs font-bold ${isToday ? "text-emerald-500 ring-1 ring-emerald-500/50 px-1.5 py-0.5 rounded-full" : "text-zinc-500"}`}>{dateObj.day}</span>
                            <div className="mt-2 space-y-1">
                                {dayEvents.map((ev, ei) => (
                                    <div key={ei} className="text-[10px] p-1 rounded border border-white/10 truncate font-bold text-zinc-200" style={{ backgroundColor: `${ev.color}20`, borderLeft: `3px solid ${ev.color}` }}>
                                        {ev.talep_eden}
                                    </div>
                                ))}
                            </div>
                        </div>
                    );
                  })}
               </div>
            </div>
            <div className="flex gap-4 p-4 bg-zinc-900/30 rounded-xl border border-white/5 text-[10px] font-bold text-zinc-500 justify-center">
                 <div className="flex items-center gap-1.5"><span className="w-2 h-2 rounded-full bg-emerald-500"></span> Onaylandı</div>
                 <div className="flex items-center gap-1.5"><span className="w-2 h-2 rounded-full bg-amber-500"></span> Beklemede</div>
                 <div className="flex items-center gap-1.5"><span className="w-2 h-2 rounded-full bg-red-500"></span> Reddedildi</div>
            </div>
          </motion.div>
        ) : (
          <motion.div
            key="list"
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: 10 }}
          >
            {loading ? (
              <div className="text-center text-zinc-500 py-12">Yükleniyor...</div>
            ) : requests.length === 0 ? (
              <div className="text-center text-zinc-500 py-12">Herhangi bir kayıt bulunamadı.</div>
            ) : (
              <div className="bg-zinc-900/50 border border-white/5 rounded-2xl overflow-hidden shadow-xl">
                <table className="w-full text-left border-collapse">
                  <thead>
                    <tr className="border-b border-white/5 bg-zinc-900/80">
                      <th className="p-4 text-xs font-semibold text-zinc-400">Tarih</th>
                      <th className="p-4 text-xs font-semibold text-zinc-400">Talep Eden</th>
                      <th className="p-4 text-xs font-semibold text-zinc-400">Açıklama</th>
                      <th className="p-4 text-xs font-semibold text-zinc-400">Durum</th>
                    </tr>
                  </thead>
                  <tbody>
                    {requests.map((r) => (
                      <tr key={r.id || r.talep_id} className="border-b border-white/5 hover:bg-white/5 transition-colors">
                        <td className="p-4 text-sm text-zinc-300">
                            {new Date(r.baslangic_tarihi).toLocaleDateString("tr-TR")}
                            {r.baslangic_tarihi !== r.bitis_tarihi && ` - ${new Date(r.bitis_tarihi).toLocaleDateString("tr-TR")}`}
                        </td>
                        <td className="p-4 text-sm text-zinc-100 font-medium">{r.talep_eden}</td>
                        <td className="p-4 text-sm text-zinc-400 truncate max-w-[200px]">{r.aciklama}</td>
                        <td className="p-4">
                          <span className="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border" style={{ backgroundColor: `${r.color}20`, color: r.color, borderColor: `${r.color}40` }}>
                            {r.durum === "onaylandi" ? "Onaylandı" : r.durum === "reddedildi" ? "Reddedildi" : "Beklemede"}
                          </span>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}
