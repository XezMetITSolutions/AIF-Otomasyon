"use client";

import { useState, useEffect } from "react";
import Image from "next/image";
import { motion, AnimatePresence } from "framer-motion";
import { Mail, Lock, Eye, EyeOff, Check, AlertCircle } from "lucide-react";
import { loginAction } from "./actions/auth";

const bgImages = [
  "https://images.unsplash.com/photo-1519817650390-64a93db51149?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80",
  "https://images.unsplash.com/photo-1542816417-0983c9c9ad53?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80",
  "https://images.unsplash.com/photo-1579294294021-d779f45d1607?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80",
  "https://images.unsplash.com/photo-1537178082695-1845112fa5b4?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80",
  "https://images.unsplash.com/photo-1580820716655-22d732c525f6?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80",
];

export default function LoginPage() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [remember, setRemember] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const [bgIndex, setBgIndex] = useState(0);

  useEffect(() => {
    const interval = setInterval(() => {
      setBgIndex((prev) => (prev + 1) % bgImages.length);
    }, 10000); // 10s slide
    return () => clearInterval(interval);
  }, []);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setError("");
    setLoading(true);

    if (!email || !password) {
      setError("E-posta ve şifre alanları boş bırakılamaz.");
      setLoading(false);
      return;
    }

    try {
      const res = await loginAction({ email, password, remember });
      if (res.success) {
        // Giriş başarılı - Dashboard'a yönlendir (İlerleyen aşamada sayfa oluşturulunca)
        window.location.href = "/dashboard"; 
      } else {
        setError(res.error || "Giriş başarısız.");
      }
    } catch (err) {
      setError("Bağlantı hatası oluştu.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="relative min-h-screen w-full flex items-center justify-center overflow-hidden font-sansSelection selection:bg-emerald-500 selection:text-white">
      {/* Background Images Layer */}
      <AnimatePresence>
        <motion.div
          key={bgImages[bgIndex]}
          initial={{ opacity: 0, scale: 1.05 }}
          animate={{ opacity: 1, scale: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 1.5 }}
          className="absolute inset-0 z-0 bg-cover bg-center"
          style={{ backgroundImage: `url(${bgImages[bgIndex]})` }}
        />
      </AnimatePresence>

      {/* Overlay Shader Gradient */}
      <div className="absolute inset-0 z-10 bg-gradient-to-br from-black/50 via-teal-950/70 to-zinc-900/90 backdrop-blur-[4px]" />

      <div className="relative z-20 w-full max-w-md px-6 py-12 flex justify-center items-center min-h-screen">
        <motion.div
          initial={{ opacity: 0, y: 30 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6, ease: "easeOut" }}
          className="w-full bg-black/40 backdrop-blur-2xl border border-white/10 rounded-3xl p-8 shadow-2xl overflow-hidden hover:border-white/20 transition-all"
        >
          {/* Logo / Header Area */}
          <div className="flex flex-col items-center mb-8">
            <motion.div
              whileHover={{ rotate: 0, scale: 1.05 }}
              initial={{ rotate: -5 }}
              className="w-20 h-20 bg-white rounded-2xl flex items-center justify-center mb-5 shadow-lg shadow-black/30 cursor-pointer overflow-hidden p-3"
            >
              <Image
                src="/AIF.png"
                alt="AIF Logo"
                width={65}
                height={65}
                className="object-contain"
              />
            </motion.div>
            <h1 className="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-white via-zinc-200 to-zinc-400 drop-shadow-md">
              AİFNET
            </h1>
            <p className="text-zinc-400 text-sm mt-1 tracking-wide">
              Yönetim Paneli Girişi
            </p>
          </div>

          {/* Form Alerts */}
          <AnimatePresence>
            {error && (
              <motion.div
                initial={{ opacity: 0, height: 0, y: -10 }}
                animate={{ opacity: 1, height: "auto", y: 0 }}
                exit={{ opacity: 0, height: 0, y: -10 }}
                className="mb-5 flex items-start gap-3 bg-red-500/10 border border-red-500/20 text-red-200 p-3 rounded-xl text-sm overflow-hidden"
              >
                <AlertCircle className="w-5 h-5 opacity-80 shrink-0 mt-0.5" />
                <span>{error}</span>
              </motion.div>
            )}
          </AnimatePresence>

          {/* Login Form */}
          <form className="space-y-5" onSubmit={handleLogin}>
            <div>
              <label className="block text-sm font-medium text-zinc-300 ml-1 mb-1.5">
                E-Posta
              </label>
              <div className="flex items-center bg-white/5 border border-white/10 rounded-xl px-4 py-3 focus-within:border-emerald-500 focus-within:bg-white/10 focus-within:ring-2 focus-within:ring-emerald-500/20 transition-all">
                <Mail className="w-5 h-5 text-zinc-500 shrink-0 mr-3" />
                <input
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="E-posta adresiniz"
                  className="bg-transparent border-none outline-none text-white w-full placeholder:text-zinc-600 text-[15px] font-medium"
                  required
                  autoFocus
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-zinc-300 ml-1 mb-1.5">
                Şifre
              </label>
              <div className="flex items-center bg-white/5 border border-white/10 rounded-xl px-4 py-3 focus-within:border-emerald-500 focus-within:bg-white/10 focus-within:ring-2 focus-within:ring-emerald-500/20 transition-all">
                <Lock className="w-5 h-5 text-zinc-500 shrink-0 mr-3" />
                <input
                  type={showPassword ? "text" : "password"}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="Şifreniz"
                  className="bg-transparent border-none outline-none text-white w-full placeholder:text-zinc-600 text-[15px] font-medium"
                  required
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="text-zinc-500 hover:text-white transition-colors"
                >
                  {showPassword ? (
                    <EyeOff className="w-5 h-5" />
                  ) : (
                    <Eye className="w-5 h-5" />
                  )}
                </button>
              </div>
            </div>

            <div className="flex justify-between items-center text-sm ml-1">
              <label className="flex items-center gap-2 cursor-pointer group">
                <div
                  role="checkbox"
                  aria-checked={remember}
                  onClick={() => setRemember(!remember)}
                  className={`w-4 h-4 rounded border flex items-center justify-center transition-all ${
                    remember
                      ? "bg-emerald-600 border-emerald-600"
                      : "border-white/30 group-hover:border-white/50 bg-white/5"
                  }`}
                >
                  {remember && <Check className="w-3 h-3 text-white" />}
                </div>
                <span className="text-zinc-400 group-hover:text-zinc-300 transition-colors">
                  Beni Hatırla
                </span>
              </label>
              <a
                href="#"
                className="text-emerald-400 hover:text-emerald-300 transition-colors font-medium decoration-emerald-500/30 hover:underline"
              >
                Şifremi Unuttum
              </a>
            </div>

            <motion.button
              whileTap={{ scale: 0.98 }}
              type="submit"
              disabled={loading}
              className="relative w-full bg-gradient-to-r from-emerald-600 to-teal-500 text-white font-bold py-3.5 rounded-xl mt-6 shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/30 hover:from-emerald-500 hover:to-teal-400 active:scale-[98%] transition-all duration-300 flex items-center justify-center disabled:opacity-75 disabled:cursor-not-allowed group overflow-hidden"
            >
              <div className="absolute inset-0 bg-white/10 translate-y-full group-hover:translate-y-0 transition-transform duration-300 ease-out" />
              {loading ? (
                <div className="w-5 h-5 border-2 border-white/40 border-t-white rounded-full animate-spin" />
              ) : (
                <span className="relative z-10 tracking-wide uppercase text-[14px]">
                  Giriş Yap
                </span>
              )}
            </motion.button>
          </form>

          {/* Footer Copyright */}
          <div className="mt-8 text-center text-zinc-500 text-[11px] font-medium tracking-wide">
            &copy; {new Date().getFullYear()} AİFNET. Tüm Hakları Saklıdır.<br />
            <span className="opacity-60 text-[10px]">v1.0.1 (Next.js Rebuild)</span>
          </div>
        </motion.div>
      </div>
    </div>
  );
}
