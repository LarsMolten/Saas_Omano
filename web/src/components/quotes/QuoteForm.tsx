"use client";

import { useState } from "react";

interface QuoteFormProps {
  providerId: number;
  providerName: string;
  onSuccess?: () => void;
}

export default function QuoteForm({
  providerId,
  providerName,
  onSuccess,
}: QuoteFormProps) {
  const [serviceType, setServiceType] = useState("");
  const [eventDate, setEventDate] = useState("");
  const [location, setLocation] = useState("");
  const [budget, setBudget] = useState("");
  const [description, setDescription] = useState("");
  const [sending, setSending] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSending(true);
    setError("");

    try {
      const res = await fetch("/api/v1/quotes", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          provider_id: providerId,
          service_type: serviceType,
          event_date: eventDate || undefined,
          location: location || undefined,
          budget: budget ? Number(budget) : undefined,
          description: description || undefined,
        }),
      });

      if (!res.ok) {
        const data = await res.json();
        throw new Error(data.message || "Erreur lors de l'envoi");
      }

      setSuccess(true);
      onSuccess?.();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Erreur inconnue");
    } finally {
      setSending(false);
    }
  }

  if (success) {
    return (
      <div className="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
        <p className="text-green-800 font-medium">
          Demande envoyee a {providerName} !
        </p>
        <p className="text-green-600 text-sm mt-1">
          Vous recevrez une reponse sous peu.
        </p>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-3">
      <h3 className="font-semibold text-gray-900">
        Demander un devis a {providerName}
      </h3>

      <div>
        <label className="block text-sm font-medium mb-1">Type de service *</label>
        <input
          type="text"
          value={serviceType}
          onChange={(e) => setServiceType(e.target.value)}
          required
          className="w-full border rounded px-3 py-2 text-sm"
          placeholder="Ex: Traiteur mariage, DJ..."
        />
      </div>

      <div className="grid grid-cols-2 gap-3">
        <div>
          <label className="block text-sm font-medium mb-1">Date</label>
          <input
            type="date"
            value={eventDate}
            onChange={(e) => setEventDate(e.target.value)}
            className="w-full border rounded px-3 py-2 text-sm"
          />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">Lieu</label>
          <input
            type="text"
            value={location}
            onChange={(e) => setLocation(e.target.value)}
            className="w-full border rounded px-3 py-2 text-sm"
            placeholder="Ville"
          />
        </div>
      </div>

      <div>
        <label className="block text-sm font-medium mb-1">Budget (OMR)</label>
        <input
          type="number"
          step="0.01"
          min="0"
          value={budget}
          onChange={(e) => setBudget(e.target.value)}
          className="w-full border rounded px-3 py-2 text-sm"
          placeholder="0.00"
        />
      </div>

      <div>
        <label className="block text-sm font-medium mb-1">Description</label>
        <textarea
          value={description}
          onChange={(e) => setDescription(e.target.value)}
          rows={3}
          className="w-full border rounded px-3 py-2 text-sm"
          placeholder="Decrivez votre evenement..."
        />
      </div>

      {error && <p className="text-red-500 text-sm">{error}</p>}

      <button
        type="submit"
        disabled={sending}
        className="w-full bg-blue-600 text-white py-2 rounded text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
      >
        {sending ? "Envoi..." : "Envoyer la demande"}
      </button>
    </form>
  );
}
