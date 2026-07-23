"use client";

import { useUser } from "@/lib/hooks/useUser";
import PortfolioSection from "@/components/portfolio/PortfolioSection";

export default function PortfolioPage() {
  const { user } = useUser();

  if (!user) return null;

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-900">Mon portfolio</h1>
      <PortfolioSection providerId={user.id} />
    </div>
  );
}
