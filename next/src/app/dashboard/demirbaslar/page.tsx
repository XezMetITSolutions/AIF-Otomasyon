"use client";

import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Box, Search, Package, MapPin, User, AlertCircle, FileSpreadsheet, Plus } from "lucide-react";
import { getDemirbaslarAction } from "../../actions/auth";

export default function DemirbaslarPage() {
  const [demirbaslar, setDemirbaslar] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [message, setMessage] = useState<{ text: string; type: "success" | "error" } | null>(null);

  useEffect(() => {
    loadData();
  }, []);

  async function loadData() {
    setLoading(true);
    const res = await getDemirbaslarAction();
    if (res.success) {
      setDemirbaslar(res.demirbaslar || []);
    } else {
      setMessage({ text: res.error || "Demirbaşlar yüklenemedi.", type: "error" });
    }
    setLoading(false);
  }

  const filteredItems = demirbaslar.filter(i => {
     const text = `${i.ad} ${i.kategori} ${i.konum} ${i.sorumlu_adi || ''}`.toLowerCase();
     return text.includes(search.toLowerCase());
  });

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'musait': return <span className="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-xs px-2 py-1 rounded-md">Müsait</span>;
      case 'kirada': return <span className="bg-amber-500/10 text-amber-500 border border-amber-500/20 text-xs px-2 py-1 rounded-md">Kirada</span>;
      case 'bakimda': return <span className="bg-sky-500/10 text-sky-400 border border-sky-500/20 text-xs px-2 py-1 rounded-md">Bakımda</span>;
      case 'arizali': return <span className="bg-red-500/10 text-red-400 border border-red-500/20 text-xs px-2 py-1 rounded-md">Arızalı</span>;
      default: return <span className="bg-zinc-800 text-zinc-400 text-xs px-2 py-1 rounded-md">{status}</span>;
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
            <Package className="w-6 h-6 text-emerald-400" /> Demirbaş Yönetimi
          </h1>
          <p className="text-zinc-500 text-sm mt-1">Sistemdeki tüm demirbaşları, konumlarını ve zimmetlilerini takip edin.</p>
        </div>
        <div className="flex gap-2">
           <button className="bg-emerald-600 hover:bg-emerald-500 text-white font-medium px-4 py-2 rounded-xl transition-all flex items-center gap-2 text-sm shadow-lg shadow-emerald-500/20">
             <Plus className="w-4 h-4" /> Yeni Ekle
           </button>
        </div>
      </div>

      <AnimatePresence>
        {message && (
          <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -10 }}
            className={`p-4 rounded-xl flex items-center gap-3 border ${message.type === "success" ? "bg-emerald-500/10 border-emerald-500/20 text-emerald-400" : "bg-red-500/10 border-red-500/20 text-red-400"}`}>
            <AlertCircle className="w-5 h-5" />
            <span className="text-sm font-medium">{message.text}</span>
          </motion.div>
        )}
      </AnimatePresence>

      <div className="bg-zinc-900 border border-white/5 rounded-2xl p-4 sticky top-20 z-10 shadow-xl shadow-black/20 flex gap-4">
         <div className="relative max-w-sm w-full">
            <Search className="w-4 h-4 absolute left-3 top-3 text-zinc-500" />
            <input type="text" placeholder="İsim, kategori veya konum ara..." value={search} onChange={(e) => setSearch(e.target.value)}
              className="w-full bg-zinc-950 border border-white/10 rounded-xl pl-10 pr-4 py-2 text-sm text-zinc-300 focus:outline-none focus:border-emerald-500/50" />
         </div>
      </div>

      {loading ? (
        <div className="flex-1 flex justify-center items-center py-20">
           <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-500"></div>
        </div>
      ) : filteredItems.length === 0 ? (
        <div className="bg-zinc-900 border border-white/5 rounded-2xl p-12 text-center flex flex-col items-center justify-center opacity-50">
           <AlertCircle className="w-12 h-12 text-zinc-500 mb-3" />
           <p className="text-zinc-400">Demirbaş bulunamadı.</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
           {filteredItems.map(item => (
              <div key={item.id} className="bg-zinc-900/40 relative border border-white/5 rounded-2xl p-5 overflow-hidden group hover:bg-zinc-800/50 transition-all">
                  <div className="flex justify-between items-start mb-3">
                     <div>
                        <span className="text-xs text-zinc-500 font-bold uppercase tracking-wider">{item.kategori}</span>
                        <h3 className="font-bold text-lg text-white mt-0.5">{item.ad}</h3>
                     </div>
                     {getStatusBadge(item.durum)}
                  </div>

                  <div className="space-y-1.5 mt-4 text-sm text-zinc-400">
                     <div className="flex items-center gap-2">
                        <MapPin className="w-3.5 h-3.5 text-zinc-500" />
                        <span>{item.konum || '-'}</span>
                     </div>
                     <div className="flex items-center gap-2">
                        <User className="w-3.5 h-3.5 text-zinc-500" />
                        <span>Sorumlu: {item.sorumlu_adi || '-'}</span>
                     </div>
                  </div>

                  {item.notlar && (
                     <p className="text-xs text-zinc-500 italic mt-3 bg-zinc-950/50 px-2 py-1.5 rounded-lg">
                        "{item.notlar}"
                     </p>
                  )}

                  <div className="absolute top-0 right-0 w-full h-full bg-zinc-950/80 backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-all flex items-center justify-center gap-3">
                     <button className="bg-zinc-800 hover:bg-zinc-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-all" title="Düzenle">
                        Görüntüle
                     </button>
                  </div>
              </div>
           ))}
        </div>
      )}
    </div>
  );
}
