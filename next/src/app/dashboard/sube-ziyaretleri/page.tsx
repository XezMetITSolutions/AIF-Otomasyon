"use client";

import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Calendar, Send, CheckCircle2, XCircle, AlertCircle, Trash2, MapPin } from "lucide-react";

export default function SubeZiyaretleriPage() {
  const [activeTab, setActiveTab] = useState<"planlanan" | "tamamlanan">("planlanan");
  const [requests, setRequests] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState<{ text: string; type: "success" | "error" } | null>(null);

  useEffect(() => {
    loadRequests();
  }, [activeTab]);

  async function loadRequests() {
    setLoading(true);
    try {
      const res = await fetch(`/api/sube-ziyaretleri.php?tab=${activeTab}`);
      const data = await res.json();
      if (data.success) {
        setRequests(data.requests || []);
      }
    } catch (e) {
      setMessage({ text: "Veriler yüklenirken hata oluştu.", type: "error" });
    }
    setLoading(false);
  }

  return (
    <div className="p-6 space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-zinc-100 flex items-center gap-2">
            <MapPin className="w-6 h-6 text-emerald-500" />
            Şube Ziyaretleri
          </h1>
          <p className="text-sm text-zinc-400">Haftalık şube ziyaret planlaması ve rapor durumu.</p>
        </div>
      </div>

      <div className="flex gap-2 border-b border-white/5 pb-2">
        <button
          onClick={() => setActiveTab("planlanan")}
          className={`px-4 py-2 rounded-lg text-sm font-medium transition-all ${
            activeTab === "planlanan" ? "bg-emerald-600 text-white" : "text-zinc-400 hover:text-white hover:bg-white/5"
          }`}
        >
          Planlanan
        </button>
        <button
          onClick={() => setActiveTab("tamamlanan")}
          className={`px-4 py-2 rounded-lg text-sm font-medium transition-all ${
            activeTab === "tamamlanan" ? "bg-emerald-600 text-white" : "text-zinc-400 hover:text-white hover:bg-white/5"
          }`}
        >
          Tamamlanan
        </button>
      </div>

      {loading ? (
        <div className="text-center text-zinc-500 py-12">Yükleniyor...</div>
      ) : requests.length === 0 ? (
        <div className="text-center text-zinc-500 py-12">Herhangi bir ziyaret kaydı bulunamadı.</div>
      ) : (
        <div className="bg-zinc-900/50 border border-white/5 rounded-2xl overflow-hidden shadow-xl">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="border-b border-white/5 bg-zinc-900/80">
                <th className="p-4 text-xs font-semibold text-zinc-400">Tarih</th>
                <th className="p-4 text-xs font-semibold text-zinc-400">Şube / BYK</th>
                <th className="p-4 text-xs font-semibold text-zinc-400">Grup / Ekip</th>
                <th className="p-4 text-xs font-semibold text-zinc-400">Durum</th>
              </tr>
            </thead>
            <tbody>
              {requests.map((r) => (
                <tr key={r.ziyaret_id} className="border-b border-white/5 hover:bg-white/5 transition-colors">
                  <td className="p-4 text-sm text-zinc-300">{new Date(r.ziyaret_tarihi).toLocaleDateString("tr-TR")}</td>
                  <td className="p-4 text-sm text-zinc-100 font-medium">{r.byk_adi}</td>
                  <td className="p-4">
                    <span className="px-2.5 py-1 rounded-full text-xs font-medium" style={{ backgroundColor: `${r.renk_kodu}20`, color: r.renk_kodu || '#10B981', border: `1px solid ${r.renk_kodu}40` }}>
                      {r.grup_adi}
                    </span>
                  </td>
                  <td className="p-4">
                    <span className={`px-2.5 py-1 rounded-full text-xs font-medium ${
                      r.durum === "tamamlandi" ? "bg-emerald-500/10 text-emerald-400" : "bg-amber-500/10 text-amber-500"
                    }`}>
                      {r.durum === "tamamlandi" ? "Tamamlandı" : "Planlandı"}
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
