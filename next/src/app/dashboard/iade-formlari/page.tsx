"use client";

import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { FileText, Send, CheckCircle2, XCircle, AlertCircle, Trash2 } from "lucide-react";

export default function IadeFormlariPage() {
  const [activeTab, setActiveTab] = useState<"talebim" | "onay">("talebim");
  const [requests, setRequests] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState<{ text: string; type: "success" | "error" } | null>(null);

  useEffect(() => {
    loadRequests();
  }, [activeTab]);

  async function loadRequests() {
    setLoading(true);
    try {
      const res = await fetch(`/api/iade-formlari.php?tab=${activeTab}`);
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
            <FileText className="w-6 h-6 text-emerald-500" />
            İade Formları
          </h1>
          <p className="text-sm text-zinc-400">Gider ve iade taleplerini yönetin.</p>
        </div>
      </div>

      <div className="flex gap-2 border-b border-white/5 pb-2">
        <button
          onClick={() => setActiveTab("talebim")}
          className={`px-4 py-2 rounded-lg text-sm font-medium transition-all ${
            activeTab === "talebim" ? "bg-emerald-600 text-white" : "text-zinc-400 hover:text-white hover:bg-white/5"
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
                <th className="p-4 text-xs font-semibold text-zinc-400">Başlık/Gider</th>
                <th className="p-4 text-xs font-semibold text-zinc-400">Tutar</th>
                <th className="p-4 text-xs font-semibold text-zinc-400">Durum</th>
              </tr>
            </thead>
            <tbody>
              {requests.map((r) => (
                <tr key={r.id || r.talep_id} className="border-b border-white/5 hover:bg-white/5 transition-colors">
                  <td className="p-4 text-sm text-zinc-300">{new Date(r.created_at || r.olusturma_tarihi).toLocaleDateString("tr-TR")}</td>
                  <td className="p-4 text-sm text-zinc-100 font-medium">{r.talep_eden || r.uye_adi}</td>
                  <td className="p-4 text-sm text-zinc-300">{r.baslik || "Masraf Formu"}</td>
                  <td className="p-4 text-sm font-semibold text-emerald-400">{r.tutar} €</td>
                  <td className="p-4">
                    <span className={`px-2.5 py-1 rounded-full text-xs font-medium ${
                      r.durum === "odenmistir" || r.durum === "onaylandi" ? "bg-emerald-500/10 text-emerald-400" : "bg-amber-500/10 text-amber-500"
                    }`}>
                      {r.durum === "odenmistir" ? "Ödendi" : r.durum === "onaylandi" ? "Onaylandı" : "Beklemede"}
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
