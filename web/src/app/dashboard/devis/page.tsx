"use client";

import QuoteList from "@/components/quotes/QuoteList";

export default function DevisRecusPage() {
  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-900">Demandes de devis recues</h1>
      <QuoteList mode="provider" />
    </div>
  );
}
