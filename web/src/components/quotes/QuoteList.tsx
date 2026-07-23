"use client";

import { useState, useEffect } from "react";
import type { QuoteRequest } from "@/lib/types/quote";

const STATUS_LABELS: Record<string, { label: string; color: string }> = {
  pending: { label: "En attente", color: "bg-yellow-100 text-yellow-800" },
  accepted: { label: "Acceptee", color: "bg-green-100 text-green-800" },
  declined: { label: "Refusee", color: "bg-red-100 text-red-800" },
  answered: { label: "Repondue", color: "bg-blue-100 text-blue-800" },
};

interface QuoteListProps {
  mode: "client" | "provider";
}

export default function QuoteList({ mode }: QuoteListProps) {
  const [quotes, setQuotes] = useState<QuoteRequest[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch("/api/v1/quotes")
      .then((res) => (res.ok ? res.json() : []))
      .then((data) => setQuotes(data))
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  function handleResponded(updated: QuoteRequest) {
    setQuotes((prev) => prev.map((q) => (q.id === updated.id ? updated : q)));
  }

  if (loading) {
    return <div className="py-8 text-center text-gray-500">Chargement...</div>;
  }

  if (quotes.length === 0) {
    return (
      <div className="text-center py-16">
        <p className="text-gray-500 text-lg">
          {mode === "client"
            ? "Aucune demande de devis envoyee."
            : "Aucune demande recue."}
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {quotes.map((quote) => {
        const status = STATUS_LABELS[quote.status] ?? STATUS_LABELS.pending;

        return (
          <div key={quote.id} className="bg-white rounded-lg shadow-sm border p-4">
            <div className="flex items-start justify-between">
              <div>
                <h3 className="font-semibold text-gray-900">{quote.service_type}</h3>
                {mode === "client" && quote.provider && (
                  <p className="text-sm text-gray-500">
                    {quote.provider.name}
                    {quote.provider.city && ` - ${quote.provider.city}`}
                  </p>
                )}
                {mode === "provider" && quote.user && (
                  <p className="text-sm text-gray-500">
                    {quote.user.name} ({quote.user.email})
                  </p>
                )}
              </div>
              <span className={`text-xs font-medium rounded px-2 py-0.5 ${status.color}`}>
                {status.label}
              </span>
            </div>

            <div className="mt-2 flex flex-wrap gap-3 text-sm text-gray-500">
              {quote.event_date && (
                <span>{new Date(quote.event_date).toLocaleDateString("fr-FR")}</span>
              )}
              {quote.location && <span>{quote.location}</span>}
              {quote.budget && <span>{quote.budget} OMR</span>}
            </div>

            {quote.description && (
              <p className="mt-2 text-sm text-gray-600">{quote.description}</p>
            )}

            {quote.provider_response && (
              <div className="mt-3 bg-gray-50 rounded p-3">
                <p className="text-xs text-gray-400 mb-1">Reponse du prestataire :</p>
                <p className="text-sm text-gray-700">{quote.provider_response}</p>
              </div>
            )}

            {mode === "provider" && quote.status === "pending" && (
              <QuoteRespondInline quote={quote} onResponded={handleResponded} />
            )}
          </div>
        );
      })}
    </div>
  );
}

function QuoteRespondInline({
  quote,
  onResponded,
}: {
  quote: QuoteRequest;
  onResponded: (q: QuoteRequest) => void;
}) {
  const [status, setStatus] = useState<"accepted" | "declined" | "answered">("answered");
  const [response, setResponse] = useState("");
  const [sending, setSending] = useState(false);
  const [error, setError] = useState("");

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSending(true);
    try {
      const res = await fetch(`/api/v1/quotes/${quote.id}/respond`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ status, provider_response: response }),
      });
      if (res.ok) {
        const updated = await res.json();
        onResponded(updated);
        setResponse("");
      } else {
        const data = await res.json();
        setError(data.message || "Erreur");
      }
    } catch {
      setError("Erreur reseau");
    } finally {
      setSending(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="mt-3 border-t pt-3 space-y-2">
      <div className="flex gap-2">
        {(["accepted", "declined", "answered"] as const).map((s) => (
          <button
            key={s}
            type="button"
            onClick={() => setStatus(s)}
            className={`px-3 py-1 rounded text-xs border ${
              status === s
                ? s === "accepted"
                  ? "bg-green-100 border-green-300 text-green-800"
                  : s === "declined"
                  ? "bg-red-100 border-red-300 text-red-800"
                  : "bg-blue-100 border-blue-300 text-blue-800"
                : "bg-white border-gray-200"
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
        rows={2}
        className="w-full border rounded px-3 py-2 text-sm"
        placeholder="Votre message..."
      />
      {error && <p className="text-red-500 text-xs">{error}</p>}
      <button
        type="submit"
        disabled={sending}
        className="bg-blue-600 text-white px-4 py-1.5 rounded text-sm hover:bg-blue-700 disabled:opacity-50"
      >
        {sending ? "..." : "Envoyer"}
      </button>
    </form>
  );
}
