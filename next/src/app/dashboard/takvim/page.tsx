"use client";

import { useState, useEffect } from "react";
import { motion } from "framer-motion";
import { Calendar as CalendarIcon, MapPin, Building, Clock, AlignLeft, CalendarCheck2, LayoutList } from "lucide-react";
import { getEtkinliklerAction } from "../../actions/auth";

export default function TakvimPage() {
  const [etkinlikler, setEtkinlikler] = useState<any[]>([]);
  const [filters, setFilters] = useState<string[]>([]);
  const [canManage, setCanManage] = useState(false);
  const [isAdmin, setIsAdmin] = useState(false);
  const [loading, setLoading] = useState(true);

  // Form states
  const [monthFilter, setMonthFilter] = useState("");
  const [yearFilter, setYearFilter] = useState(new Date().getFullYear().toString());
  const [birimFilter, setBirimFilter] = useState("");

  useEffect(() => {
    loadEtkinlikler();
  }, [monthFilter, yearFilter, birimFilter]);

  async function loadEtkinlikler() {
    setLoading(true);
    const res = await getEtkinliklerAction({ ay: monthFilter, yil: yearFilter, birim: birimFilter });
    if (res.success) {
      setEtkinlikler(res.etkinlikler || []);
      setFilters(res.filters || []); // AT, KGT, vb.
      setCanManage(res.canManage || false);
      setIsAdmin(res.isAdmin || false);
    }
    setLoading(false);
  }

  const getMonths = () => {
    return [
      { v: "1", l: "Ocak" }, { v: "2", l: "Şubat" }, { v: "3", l: "Mart" },
      { v: "4", l: "Nisan" }, { v: "5", l: "Mayıs" }, { v: "6", l: "Haziran" },
      { v: "7", l: "Temmuz" }, { v: "8", l: "Ağustos" }, { v: "9", l: "Eylül" },
      { v: "10", l: "Ekim" }, { v: "11", l: "Kasım" }, { v: "12", l: "Aralık" }
    ];
  };

  const getYears = () => {
    const list = [];
    for (let i = 2022; i <= 2030; i++) list.push(i.toString());
    return list;
  };

  const parseEventTime = (baslangicStr: string, bitisStr: string) => {
    const s = new Date(baslangicStr);
    const e = new Date(bitisStr);
    const timeS = s.toLocaleTimeString("tr-TR", { hour: "2-digit", minute: "2-digit" });
    const timeE = e.toLocaleTimeString("tr-TR", { hour: "2-digit", minute: "2-digit" });

    return {
      day: s.getDate().toString().padStart(2, "0"),
      monthStr: s.toLocaleString("tr-TR", { month: "short" }).toUpperCase(),
      timeString: (timeS === "00:00" && timeE === "23:59") ? "Tüm Gün" : `${timeS} - ${timeE}`,
      isPast: e < new Date(),
    };
  };

  return (
    <div className="space-y-6">
      {/* BAŞLIK */}
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
            <CalendarCheck2 className="w-6 h-6 text-emerald-400" /> Çalışma Takvimi
          </h1>
          <p className="text-zinc-500 text-sm mt-1">Birimlerin tüm planlanmış etkinliklerini buradan takip edebilirsiniz.</p>
        </div>
      </div>

      {/* FİLTRELER */}
      <div className="bg-zinc-900 border border-white/5 rounded-2xl p-4 sticky top-20 z-10 shadow-xl shadow-black/20 flex flex-wrap gap-4">
        <select 
          value={monthFilter}
          onChange={(e) => setMonthFilter(e.target.value)}
          className="bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-zinc-300 focus:outline-none focus:border-emerald-500/50 min-w-[130px]"
        >
          <option value="">Tüm Aylar</option>
          {getMonths().map(m => (
            <option key={m.v} value={m.v}>{m.l}</option>
          ))}
        </select>

        <select 
          value={yearFilter}
          onChange={(e) => setYearFilter(e.target.value)}
          className="bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-zinc-300 focus:outline-none focus:border-emerald-500/50 min-w-[120px]"
        >
          {getYears().map(y => (
            <option key={y} value={y}>{y}</option>
          ))}
        </select>

        {filters.length > 0 && (
          <div className="flex bg-zinc-950 rounded-xl p-1 border border-white/10 w-full md:w-auto overflow-x-auto hide-scrollbar">
            <button 
              onClick={() => setBirimFilter("")}
              className={`px-4 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-all ${birimFilter === "" ? "bg-emerald-500/20 text-emerald-400" : "text-zinc-400 hover:text-white"}`}
            >
              Tümü
            </button>
            {filters.map(f => (
              <button 
                key={f}
                onClick={() => setBirimFilter(f)}
                className={`px-4 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-all flex items-center gap-1 ${birimFilter === f ? "bg-emerald-500/20 text-emerald-400" : "text-zinc-400 hover:text-white"}`}
              >
                {f}
              </button>
            ))}
          </div>
        )}
      </div>

      {/* LİSTE GÖRÜNÜMÜ */}
      {loading ? (
        <div className="flex justify-center items-center py-20 text-emerald-500 font-bold">Takvim Yükleniyor...</div>
      ) : etkinlikler.length === 0 ? (
        <div className="bg-zinc-900 border border-white/5 rounded-2xl p-12 text-center flex flex-col items-center justify-center space-y-3">
          <div className="w-16 h-16 bg-zinc-800 rounded-full flex items-center justify-center opacity-50 mb-2">
            <CalendarIcon className="w-8 h-8 text-zinc-500" />
          </div>
          <h3 className="text-zinc-300 font-semibold">Etkinlik Bulunamadı</h3>
          <p className="text-zinc-500 text-sm max-w-sm">
            Seçtiğiniz tarihte planlanmış bir çalışma veya etkinlik bulunmuyor.
          </p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {etkinlikler.map((e, idx) => {
            const parsed = parseEventTime(e.baslangic_tarihi, e.bitis_tarihi);
            
            return (
              <motion.div 
                initial={{ opacity: 0, scale: 0.98 }}
                animate={{ opacity: 1, scale: 1 }}
                transition={{ delay: idx * 0.05 }}
                key={e.etkinlik_id}
                className={`flex gap-4 p-5 rounded-2xl border transition-all ${
                  parsed.isPast 
                    ? "bg-zinc-900/40 border-white/5 opacity-70" 
                    : "bg-zinc-900/80 border-white/10 hover:border-emerald-500/30 hover:bg-zinc-900 hover:shadow-lg"
                }`}
              >
                {/* Tarih Rozeti */}
                <div className="flex flex-col items-center justify-center bg-zinc-950 border border-white/5 rounded-xl min-w-[70px] h-[70px]">
                  <span className="text-2xl font-bold leading-none text-emerald-400">{parsed.day}</span>
                  <span className="text-xs font-bold tracking-widest text-zinc-500 mt-1">{parsed.monthStr}</span>
                </div>

                {/* İçerik */}
                <div className="flex-1 min-w-0">
                  <div className="flex justify-between items-start gap-2">
                    <h3 className="font-bold text-white text-base truncate pr-2">{e.baslik}</h3>
                  </div>

                  <div className="flex flex-col gap-1.5 mt-2">
                    <div className="flex items-center gap-2 text-xs text-zinc-400 font-medium">
                      <Clock className="w-3.5 h-3.5 text-zinc-500" /> {parsed.timeString}
                    </div>
                    {e.konum && (
                      <div className="flex items-center gap-2 text-xs text-zinc-400">
                        <MapPin className="w-3.5 h-3.5 text-zinc-500" /> <span className="truncate">{e.konum}</span>
                      </div>
                    )}
                  </div>
                  
                  {e.aciklama && (
                    <p className="mt-3 text-xs text-zinc-500 leading-relaxed line-clamp-2">
                      <AlignLeft className="w-3.5 h-3.5 inline mr-1 opacity-50 relative -top-0.5" />
                      {e.aciklama.replace(/<[^>]*>?/gm, '')}
                    </p>
                  )}

                  <div className="mt-4 pt-3 border-t border-white/5 flex justify-between items-center">
                    <div className="flex items-center gap-1.5">
                      <div className="w-2 h-2 rounded-full" style={{ backgroundColor: e.byk_renk || '#10b981' }}></div>
                      <span className="text-[10px] font-bold tracking-wider text-zinc-400 uppercase">{e.byk_kodu || e.byk_adi}</span>
                    </div>
                    {canManage && (
                      <span className="text-[10px] text-zinc-600 font-medium">Id: {e.etkinlik_id}</span>
                    )}
                  </div>
                </div>
              </motion.div>
            );
          })}
        </div>
      )}
    </div>
  );
}
