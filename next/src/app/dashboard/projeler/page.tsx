"use client";

import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { FolderKanban, Plus, Eye, Edit, Trash, Calendar } from "lucide-react";

export default function ProjelerimPage() {
  const [activeTab, setActiveTab] = useState<"aktif" | "tamamlandi">("aktif");
  const [requests, setRequests] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [canManage, setCanManage] = useState(false);
  const [message, setMessage] = useState<{ text: string; type: "success" | "error" } | null>(null);

  useEffect(() => {
    loadRequests();
  }, [activeTab]);

  async function loadRequests() {
    setLoading(true);
    try {
      const res = await fetch(`/api/projeler.php`);
      const data = await res.json();
      if (data.success) {
        setRequests(data.requests || []);
        setCanManage(data.canManage);
      }
    } catch (e) {
      setMessage({ text: "Veriler yüklenirken hata oluştu.", type: "error" });
    }
    setLoading(false);
  }

  const filteredProjects = requests.filter((r) => {
    if (activeTab === "aktif") return r.durum !== "tamamlandi";
    return r.durum === "tamamlandi";
  });

  const getStatusClass = (durum: string) => {
    switch (durum) {
      case "tamamlandi": return "bg-emerald-500/10 text-emerald-400 border-emerald-500/20";
      case "aktif": return "bg-blue-500/10 text-blue-400 border-blue-500/20";
      case "iptal": return "bg-red-500/10 text-red-400 border-red-500/20";
      default: return "bg-amber-500/10 text-amber-500 border-amber-500/20";
    }
  };

  return (
    <div className="p-6 space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-zinc-100 flex items-center gap-2">
            <FolderKanban className="w-6 h-6 text-emerald-500" />
            Proje Takibi
          </h1>
          <p className="text-sm text-zinc-400">Yürütülen ve planlanan projelerin durumunu izleyin.</p>
        </div>
        {canManage && (
          <button className="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-sm font-semibold transition-all shadow-lg shadow-emerald-500/10">
            <Plus className="w-4 h-4" />
            Yeni Proje
          </button>
        )}
      </div>

      <div className="flex gap-2 border-b border-white/5 pb-2">
        <button
          onClick={() => setActiveTab("aktif")}
          className={`px-4 py-2 rounded-lg text-sm font-medium transition-all ${
            activeTab === "aktif" ? "bg-emerald-600 text-white" : "text-zinc-400 hover:text-white hover:bg-white/5"
          }`}
        >
          Yürütülen Projeler
        </button>
        <button
          onClick={() => setActiveTab("tamamlandi")}
          className={`px-4 py-2 rounded-lg text-sm font-medium transition-all ${
            activeTab === "tamamlandi" ? "bg-emerald-600 text-white" : "text-zinc-400 hover:text-white hover:bg-white/5"
          }`}
        >
          Tamamlananlar
        </button>
      </div>

      {loading ? (
        <div className="text-center text-zinc-500 py-12">Yükleniyor...</div>
      ) : filteredProjects.length === 0 ? (
        <div className="text-center text-zinc-500 py-12">Herhangi bir proje bulunamadı.</div>
      ) : (
        <div className="bg-zinc-900/50 border border-white/5 rounded-2xl overflow-hidden shadow-xl">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="border-b border-white/5 bg-zinc-900/80">
                <th className="p-4 text-xs font-semibold text-zinc-400">Proje Adı</th>
                <th className="p-4 text-xs font-semibold text-zinc-400">Birim/BYK</th>
                <th className="p-4 text-xs font-semibold text-zinc-400">Sorumlu</th>
                <th className="p-4 text-xs font-semibold text-zinc-400">Dönem/Tarih</th>
                <th className="p-4 text-xs font-semibold text-zinc-400">Durum</th>
              </tr>
            </thead>
            <tbody>
              {filteredProjects.map((r) => (
                <tr key={r.proje_id} className="border-b border-white/5 hover:bg-white/5 transition-colors">
                  <td className="p-4 text-sm text-zinc-100 font-medium">{r.baslik}</td>
                  <td className="p-4 text-sm text-zinc-400">{r.byk_adi}</td>
                  <td className="p-4 text-sm text-zinc-300">
                    {r.sorumlu ? (
                      <div className="flex items-center gap-2">
                        <div className="w-6 h-6 rounded-full bg-emerald-600/20 text-emerald-400 flex items-center justify-center text-xs font-semibold">
                          {r.sorumlu.substring(0, 1).toUpperCase()}
                        </div>
                        {r.sorumlu}
                      </div>
                    ) : "-"}
                  </td>
                  <td className="p-4 text-sm text-zinc-400">
                    {r.baslangic_tarihi ? new Date(r.baslangic_tarihi).toLocaleDateString("tr-TR") : ""}
                    {r.baslangic_tarihi && r.bitis_tarihi ? " - " : ""}
                    {r.bitis_tarihi ? new Date(r.bitis_tarihi).toLocaleDateString("tr-TR") : ""}
                  </td>
                  <td className="p-4">
                    <span className={`px-2.5 py-1 rounded-md text-xs font-medium border ${getStatusClass(r.durum)}`}>
                      {r.durum ? r.durum.charAt(0).toUpperCase() + r.durum.slice(1) : "Beklemede"}
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
