"use client";

import Link from "next/link";
import { useState } from "react";
import NotificationBell from "./NotificationBell";

export default function Header() {
  const [menuOpen, setMenuOpen] = useState(false);

  return (
    <header className="border-b border-gray-200 bg-white">
      <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
        <Link href="/" className="text-xl font-bold text-gray-900">
          Omano
        </Link>

        {/* Desktop nav */}
        <nav className="hidden md:flex items-center gap-4">
          <Link href="/recherche" className="text-sm text-gray-600 hover:text-gray-900">
            Recherche
          </Link>
          <Link href="/categories/traiteur" className="text-sm text-gray-600 hover:text-gray-900">
            Categories
          </Link>
          <Link href="/mes-devis" className="text-sm text-gray-600 hover:text-gray-900">
            Mes devis
          </Link>
          <Link href="/favoris" className="text-sm text-gray-600 hover:text-gray-900">
            Favoris
          </Link>
          <NotificationBell />
        </nav>

        {/* Mobile hamburger + bell */}
        <div className="flex items-center gap-2 md:hidden">
          <NotificationBell />
          <button
            onClick={() => setMenuOpen(!menuOpen)}
            className="p-2 text-gray-600 hover:text-gray-900"
            aria-label="Menu"
          >
            {menuOpen ? (
              <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            ) : (
              <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            )}
          </button>
        </div>
      </div>

      {/* Mobile dropdown */}
      {menuOpen && (
        <nav className="md:hidden border-t border-gray-100 bg-white px-4 py-3 space-y-2">
          <Link href="/recherche" onClick={() => setMenuOpen(false)} className="block py-2 text-sm text-gray-600 hover:text-gray-900">
            Recherche
          </Link>
          <Link href="/categories/traiteur" onClick={() => setMenuOpen(false)} className="block py-2 text-sm text-gray-600 hover:text-gray-900">
            Categories
          </Link>
          <Link href="/mes-devis" onClick={() => setMenuOpen(false)} className="block py-2 text-sm text-gray-600 hover:text-gray-900">
            Mes devis
          </Link>
          <Link href="/favoris" onClick={() => setMenuOpen(false)} className="block py-2 text-sm text-gray-600 hover:text-gray-900">
            Favoris
          </Link>
        </nav>
      )}
    </header>
  );
}
