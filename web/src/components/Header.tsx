"use client";

import Link from "next/link";
import NotificationBell from "./NotificationBell";

export default function Header() {
  return (
    <header className="border-b border-gray-200 bg-white">
      <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
        <Link href="/" className="text-xl font-bold text-gray-900">
          Omano
        </Link>
        <nav className="flex items-center gap-4">
          <Link href="/recherche" className="text-sm text-gray-600 hover:text-gray-900">
            Recherche
          </Link>
          <Link href="/mes-devis" className="text-sm text-gray-600 hover:text-gray-900">
            Mes devis
          </Link>
          <Link href="/favoris" className="text-sm text-gray-600 hover:text-gray-900">
            Favoris
          </Link>
          <NotificationBell />
        </nav>
      </div>
    </header>
  );
}
