"use client";

import { useEffect } from "react";
import { useRouter, usePathname } from "next/navigation";
import Link from "next/link";
import { useUser } from "@/lib/hooks/useUser";

const NAV = [
  { href: "/dashboard", label: "Vue d'ensemble", icon: "M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0h4" },
  { href: "/dashboard/profil", label: "Mon profil", icon: "M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" },
  { href: "/dashboard/services", label: "Services", icon: "M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" },
  { href: "/dashboard/portfolio", label: "Portfolio", icon: "M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" },
  { href: "/dashboard/devis", label: "Devis recus", icon: "M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" },
  { href: "/dashboard/statistiques", label: "Statistiques", icon: "M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" },
  { href: "/dashboard/abonnement", label: "Abonnement", icon: "M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" },
];

export default function DashboardLayout({ children }: { children: React.ReactNode }) {
  const { user, loading, error } = useUser();
  const router = useRouter();
  const pathname = usePathname();

  useEffect(() => {
    if (!loading && error) {
      router.push("/login");
    }
    if (!loading && user && user.role !== "prestataire" && user.role !== "admin") {
      router.push("/client");
    }
  }, [loading, error, user, router]);

  if (loading) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-gray-50">
        <div className="h-8 w-8 animate-spin rounded-full border-4 border-blue-600 border-t-transparent" />
      </div>
    );
  }

  if (error || !user || (user.role !== "prestataire" && user.role !== "admin")) {
    return null;
  }

  const isUnverified = !user.email_verified_at;

  return (
    <div className="flex min-h-screen bg-gray-50">
      {/* Sidebar */}
      <aside className="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-gray-200 transform transition-transform duration-200 lg:translate-x-0 lg:static lg:z-auto">
        <div className="flex h-16 items-center border-b border-gray-200 px-6">
          <Link href="/dashboard" className="text-lg font-bold text-gray-900">
            Espace prestataire
          </Link>
        </div>
        <nav className="mt-4 space-y-1 px-3">
          {NAV.map((item) => {
            const active = pathname === item.href || (item.href !== "/dashboard" && pathname.startsWith(item.href));
            return (
              <Link
                key={item.href}
                href={item.href}
                className={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors ${
                  active
                    ? "bg-blue-50 text-blue-700"
                    : "text-gray-600 hover:bg-gray-50 hover:text-gray-900"
                }`}
              >
                <svg className="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                  <path strokeLinecap="round" strokeLinejoin="round" d={item.icon} />
                </svg>
                {item.label}
              </Link>
            );
          })}
        </nav>
        <div className="absolute bottom-0 left-0 right-0 border-t border-gray-200 p-4">
          <Link href="/" className="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900">
            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Retour au site
          </Link>
        </div>
      </aside>

      {/* Main */}
      <div className="flex flex-1 flex-col">
        {/* Verification banner */}
        {isUnverified && (
          <div className="bg-amber-50 border-b border-amber-200 px-4 py-3">
            <div className="max-w-7xl mx-auto flex items-center gap-3">
              <svg className="h-5 w-5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
              <p className="text-sm text-amber-800">
                Votre email n&apos;est pas encore verifie.{" "}
                <Link href="/dashboard/profil" className="font-medium underline">
                  Completer la verification
                </Link>
              </p>
            </div>
          </div>
        )}

        {/* Top bar */}
        <header className="flex h-16 items-center border-b border-gray-200 bg-white px-4 lg:px-6">
          <h2 className="text-sm font-medium text-gray-500">
            Bonjour, {user.name}
          </h2>
          <div className="ml-auto flex items-center gap-3">
            <Link href={`/prestataire/${user.slug}`} className="text-sm text-blue-600 hover:text-blue-700" target="_blank">
              Voir mon profil public
            </Link>
          </div>
        </header>

        <main className="flex-1 p-4 lg:p-8">{children}</main>
      </div>
    </div>
  );
}
