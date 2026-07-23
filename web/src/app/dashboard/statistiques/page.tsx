import type { Metadata } from "next";
import StatsDashboard from "@/components/stats/StatsDashboard";

export const metadata: Metadata = {
  title: "Statistiques | Omano",
  description: "Consultez les statistiques de votre activité.",
};

export default function StatistiquesPage() {
  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 py-6">
          <h1 className="text-2xl font-bold text-gray-900">Statistiques</h1>
          <p className="mt-1 text-gray-600">Suivez les performances de votre activité</p>
        </div>
      </header>
      <main className="max-w-7xl mx-auto px-4 py-8">
        <StatsDashboard />
      </main>
    </div>
  );
}
