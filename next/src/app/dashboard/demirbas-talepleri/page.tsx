"use client";

import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Box, Send, CheckCircle2, XCircle, AlertCircle, Plus } from "lucide-react";
import { getDemirbasTalepleriAction, actionDemirbasTalebi } from "../../actions/auth";

export default function DemirbasTalepleriPage() {
  const [activeTab, setActiveTab] = useState<"talep" | "onay">("talep");
  const [requests, setRequests] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [message, setMessage] = useState<{ text: string; type: "success" | "error" } | null>(null);

  // Form Stats
  const [demirbasAdi, setDemirbasAdi] = useState("");
  const [materyal, setMateryal] = useState("");
  const [aciklama, setAciklama] = useState("");
  const [submitLoading, setSubmitLoading] = useState(false);

  useEffect(() => {
    loadRequests();
  }, [activeTab]);

  async function loadRequests() {
    setLoading(true);
    try {
      const res = await getDemirbasTalepleriAction({ tab: activeTab });
      if (res.success) {
        setRequests(res.requests || []);
      } else {
        setMessage({ text: res.error || "Talepler yüklenemedi.", type: "error" });
      }
    } catch (e) {
      setMessage({ text: "Veriler yüklenirken hata oluştu.", type: "error" });
    }
    setLoading(false);
  }

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!demirbasAdi) return;
    setSubmitLoading(true);

    try {
      const res = await actionDemirbasTalebi({ action: "yeni_talep", demirbas_adi: demirbasAdi, materyal, aciklama });
      if (res.success) {
        setIsModalOpen(false);
        setDemirbasAdi(""); setMateryal(""); setAciklama("");
        setMessage({ text: "Demirbaş talebi başarıyla oluşturuldu.", type: "success" });
        loadRequests();
      } else {
        setMessage({ text: res.error || "Hata oluştu.", type: "error" });
      }
    } catch (e) {
      setMessage({ text: "Talep gönderilemedi.", type: "error" });
    }
    setSubmitLoading(false);
  };

  return (
    <div className="p-6 space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-zinc-100 flex items-center gap-2">
            <Box className="w-6 h-6 text-emerald-500" />
            Demirbaş Talepleri
          </h1>
          <p className="text-sm text-zinc-400">Demirbaş ve ofis ihtiyaç onay süreçlerini izleyin.</p>
        </div>
        <button 
          onClick={() => setIsModalOpen(true)}
          className="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-sm font-semibold transition-all shadow-lg"
        >
          <Plus className="w-4 h-4" />
          Yeni Talep Ekle
        </button>
      </div>

      <div className="flex gap-2 border-b border-white/5 pb-2">
        <button onClick={() => setActiveTab("talep")} className={`px-4 py-2 rounded-lg text-sm font-medium ${activeTab === "talep" ? "bg-emerald-600 text-white" : "text-zinc-400"}`}>Taleplerim</button>
        <button onClick={() => setActiveTab("onay")} className={`px-4 py-2 rounded-lg text-sm font-medium ${activeTab === "onay" ? "bg-emerald-600 text-white" : "text-zinc-400"}`}>Onay Bekleyenler</button>
      </div>

      {message && (
        <div className={`p-4 rounded-xl text-sm ${message.type === "success" ? "bg-emerald-500/10 text-emerald-400" : "bg-red-500/10 text-red-500"}`}>
          {message.text}
        </div>
      )}

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
                <th className="p-4 text-xs font-semibold text-zinc-400">Demirbaş Adı</th>
                <th className="p-4 text-xs font-semibold text-zinc-400">Materyal</th>
                <th className="p-4 text-xs font-semibold text-zinc-400">Durum</th>
              </tr>
            </thead>
            <tbody>
              {requests.map((r, idx) => (
                <tr key={idx} className="border-b border-white/5 hover:bg-white/5 transition-colors">
                  <td className="p-4 text-sm text-zinc-300">{new Date(r.created_at || r.olusturma_tarihi).toLocaleDateString("tr-TR")}</td>
                  <td className="p-4 text-sm text-zinc-100 font-medium">{r.talep_eden || r.uye_adi}</td>
                  <td className="p-4 text-sm text-zinc-300">{r.demirbas_adi || "Ofis Malzemesi"}</td>
                  <td className="p-4 text-sm text-zinc-300">{r.materyal || "-"}</td>
                  <td className="p-4">
                    <span className={`px-2.5 py-1 rounded-full text-xs font-medium ${r.durum === "onaylandi" ? "bg-emerald-500/10 text-emerald-400" : "bg-amber-500/10 text-amber-500"}`}>
                      {r.durum === "onaylandi" ? "Onaylandı" : "Beklemede"}
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Creation Modal */}
      <AnimatePresence>
        {isModalOpen && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <motion.div initial={{ opacity: 0, scale: 0.95 }} animate={{ opacity: 1, scale: 1 }} exit={{ opacity: 0, scale: 0.95 }} className="bg-zinc-900 border border-white/10 p-6 rounded-2xl shadow-xl w-full max-w-md">
              <h2 className="text-xl font-bold text-white mb-4">Yeni Demirbaş Talebi</h2>
              <form onSubmit={handleCreate} className="space-y-4">
                <div>
                  <label className="block text-xs text-zinc-400 mb-1">Demirbaş Adı</label>
                  <input type="text" value={demirbasAdi} onChange={(e) => setDemirbasAdi(e.target.value)} required className="w-full bg-zinc-800 border border-white/10 rounded-lg px-3 py-2 text-white text-sm" />
                </div>
                <div>
                  <label className="block text-xs text-zinc-400 mb-1">Turu / Materyal</label>
                  <input type="text" value={materyal} onChange={(e) => setMateryal(e.target.value)} placeholder="ör. Bilgisayar, Koltuk" className="w-full bg-zinc-800 border border-white/10 rounded-lg px-3 py-2 text-white text-sm" />
                </div>
                <div>
                  <label className="block text-xs text-zinc-400 mb-1">Açıklama / Adet</label>
                  <textarea value={aciklama} onChange={(e) => setAciklama(e.target.value)} className="w-full bg-zinc-800 border border-white/10 rounded-lg px-3 py-2 text-white text-sm" rows={3}></textarea>
                </div>
                <div className="flex gap-2 pt-2">
                  <button type="submit" disabled={submitLoading} className="flex-1 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-sm font-semibold disabled:opacity-50">
                    {submitLoading ? "Gönderiliyor..." : "Gönder"}
                  </button>
                  <button type="button" onClick={() => setIsModalOpen(false)} className="flex-1 py-2 bg-zinc-800 hover:bg-zinc-700 text-white rounded-xl text-sm font-semibold border border-white/5">İptal</button>
                </div>
              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
    </div>
  );
}
