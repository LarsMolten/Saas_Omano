"use client";

import { useState } from "react";
import type { QuoteRequest } from "@/lib/types/quote";

interface QuoteRespondFormProps {
  quote: QuoteRequest;
  onResponded: (updated: QuoteRequest) => void;
}

export default function QuoteRespondForm({
  quote,
  onResponded,
}: QuoteRespondFormProps) {
  const [status, setStatus] = useState<"accepted" | "declined" | "answered">("answered");
  const [response, setResponse] = useState("");
  const [sending, setSending] = useState(false);
  const [error, setError] = useState("");

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSending(true);
    setError("");

    try {
      const res = await fetch(`/api/v1/quotes/${quote.id}/respond`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ status, provider_response: response }),
      });

      if (!res.ok) {
        const data = await res.json();
        throw new Error(data.message || "Erreur");
      }

      const updated = await res.json();
      onResponded(updated);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Erreur inconnue");
    } finally {
      setSending(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-3 border-t pt-3 mt-3">
      <h4 className="text-sm font-medium text-gray-700">Repondre</h4>

      <div className="flex gap-2">
        {(["accepted", "declined", "answered"] as const).map((s) => (
          <button
            key={s}
            type="button"
            onClick={() => setStatus(s)}
            className={`px-3 py-1 rounded text-sm border ${
              status === s
                ? s === "accepted"
                  ? "bg-green-100 border-green-300 text-green-800"
                  : s === "declined"
                  ? "bg-red-100 border-red-300 text-red-800"
                  : "bg-blue-100 border-blue-300 text-blue-800"
                : "bg-white border-gray-200 text-gray-600"
            }`}
          >
            {s === "accepted" ? "Accepter" : s === "declined" ? "Refuser" : "Repondre"}
          </button>
        ))}
      </div>

      <textarea
        value={response}
        onChange={(e) => setResponse(e.target.value)}
        required
        rows={3}
        className="w-full border rounded px-3 py-2 text-sm"
        placeholder="Votre message..."
      />

      {error && <p className="text-red-500 text-sm">{error}</p>}

      <button
        type="submit"
        disabled={sending}
        className="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700 disabled:opacity-50"
      >
        {sending ? "Envoi..." : "Envoyer"}
      </button>
    </form>
  );
}
