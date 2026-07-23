"use client";

import { useState } from "react";

export default function VerifyPhonePage() {
  const [code, setCode] = useState("");
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const [sending, setSending] = useState(false);

  async function handleSendCode() {
    setSending(true);
    setError("");

    try {
      const res = await fetch("/api/auth/send-phone-verification", {
        method: "POST",
      });

      const data = await res.json();

      if (!res.ok) {
        setError(data.message || "Erreur lors de l'envoi.");
        return;
      }

      setMessage(data.message);
    } catch {
      setError("Erreur réseau.");
    } finally {
      setSending(false);
    }
  }

  async function handleVerify(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setError("");
    setMessage("");
    setLoading(true);

    try {
      const res = await fetch("/api/auth/verify-phone", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ code }),
      });

      const data = await res.json();

      if (!res.ok) {
        setError(data.message || "Code invalide.");
        return;
      }

      setMessage(data.message);
    } catch {
      setError("Erreur réseau.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50">
      <div className="w-full max-w-md bg-white rounded-lg shadow-md p-8">
        <h1 className="text-2xl font-bold text-center mb-6">Vérifier votre téléphone</h1>

        {message && (
          <div className="bg-green-50 text-green-700 p-3 rounded mb-4 text-sm">
            {message}
          </div>
        )}

        {error && (
          <div className="bg-red-50 text-red-700 p-3 rounded mb-4 text-sm">
            {error}
          </div>
        )}

        <div className="space-y-4">
          <button
            onClick={handleSendCode}
            disabled={sending}
            className="w-full bg-gray-600 text-white py-2 rounded-md hover:bg-gray-700 disabled:opacity-50"
          >
            {sending ? "Envoi en cours..." : "Envoyer le code SMS"}
          </button>

          <form onSubmit={handleVerify} className="space-y-4">
            <div>
              <label htmlFor="code" className="block text-sm font-medium text-gray-700 mb-1">
                Code SMS
              </label>
              <input
                id="code"
                type="text"
                value={code}
                onChange={(e) => setCode(e.target.value)}
                required
                maxLength={6}
                className="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="6 chiffres"
              />
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 disabled:opacity-50"
            >
              {loading ? "Vérification..." : "Vérifier le code"}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
