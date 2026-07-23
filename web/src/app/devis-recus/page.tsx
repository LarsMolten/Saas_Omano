import type { Metadata } from "next";
import QuoteList from "@/components/quotes/QuoteList";

export const metadata: Metadata = {
  title: "Demandes recues | Omano",
  description: "Consultez les demandes de devis recues de vos clients.",
};

export default function DevisRecusPage() {
  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 py-6">
          <h1 className="text-2xl font-bold text-gray-900">Demandes recues</h1>
        </div>
      </header>
      <main className="max-w-3xl mx-auto px-4 py-8">
        <QuoteList mode="provider" />
      </main>
    </div>
  );
}
