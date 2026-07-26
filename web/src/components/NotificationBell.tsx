"use client";

import { useState, useEffect, useCallback, useRef } from "react";

interface Notification {
  id: number;
  type: string;
  payload: Record<string, unknown>;
  read_at: string | null;
  created_at: string;
}

function formatNotification(n: Notification): string {
  switch (n.type) {
    case "quote.received":
      return `Nouvelle demande de devis de ${n.payload.client_name ?? "un client"}`;
    case "quote.responded":
      return `Votre devis a été ${n.payload.status === "accepted" ? "accepté" : "refusé"}`;
    case "review.received":
      return `Nouvel avis ${n.payload.rating}/5 de ${n.payload.client_name ?? "un client"}`;
    case "review.reported":
      return "Un avis a été signalé";
    default:
      return "Nouvelle notification";
  }
}

export default function NotificationBell() {
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [open, setOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const panelRef = useRef<HTMLDivElement>(null);

  const fetchNotifications = useCallback(async (p: number) => {
    setLoading(true);
    try {
      const res = await fetch(`/api/v1/notifications?page=${p}&per_page=10`);
      if (res.ok) {
        const data = await res.json();
        if (p === 1) {
          setNotifications(data.data);
        } else {
          setNotifications((prev) => [...prev, ...data.data]);
        }
        setUnreadCount(data.unread_count);
        setHasMore(p < data.meta.last_page);
      }
    } catch {
      // silent
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchNotifications(1);
  }, [fetchNotifications]);

  useEffect(() => {
    if (!open) return;

    function handleClickOutside(e: MouseEvent) {
      if (panelRef.current && !panelRef.current.contains(e.target as Node)) {
        setOpen(false);
      }
    }

    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, [open]);

  async function markAsRead(id: number) {
    try {
      const res = await fetch(`/api/v1/notifications/${id}/read`, {
        method: "PATCH",
      });
      if (res.ok) {
        setNotifications((prev) =>
          prev.map((n) => (n.id === id ? { ...n, read_at: new Date().toISOString() } : n))
        );
        setUnreadCount((prev) => Math.max(0, prev - 1));
      }
    } catch {
      // silent
    }
  }

  async function markAllAsRead() {
    try {
      const res = await fetch("/api/v1/notifications/read-all", {
        method: "PATCH",
      });
      if (res.ok) {
        setNotifications((prev) =>
          prev.map((n) => (n.read_at ? n : { ...n, read_at: new Date().toISOString() }))
        );
        setUnreadCount(0);
      }
    } catch {
      // silent
    }
  }

  function loadMore() {
    const nextPage = page + 1;
    setPage(nextPage);
    fetchNotifications(nextPage);
  }

  return (
    <div className="relative" ref={panelRef}>
      <button
        onClick={() => setOpen(!open)}
        className="relative p-2 text-gray-600 hover:text-gray-900"
        title="Notifications"
      >
        <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
          <path strokeLinecap="round" strokeLinejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        {unreadCount > 0 && (
          <span className="absolute -top-0.5 -right-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
            {unreadCount > 9 ? "9+" : unreadCount}
          </span>
        )}
      </button>

      {open && (
        <div className="absolute right-0 mt-2 w-80 max-w-[calc(100vw-2rem)] max-h-96 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg z-50">
          <div className="flex items-center justify-between border-b border-gray-100 px-4 py-3">
            <h3 className="text-sm font-semibold text-gray-900">Notifications</h3>
            {unreadCount > 0 && (
              <button
                onClick={markAllAsRead}
                className="text-xs text-blue-600 hover:underline"
              >
                Tout marquer lu
              </button>
            )}
          </div>

          {notifications.length === 0 && !loading && (
            <p className="px-4 py-6 text-center text-sm text-gray-500">Aucune notification</p>
          )}

          {notifications.map((n) => (
            <button
              key={n.id}
              onClick={() => markAsRead(n.id)}
              className={`block w-full px-4 py-3 text-left text-sm hover:bg-gray-50 transition-colors ${
                n.read_at ? "text-gray-600" : "text-gray-900 bg-blue-50/50"
              }`}
            >
              <p className="leading-snug">{formatNotification(n)}</p>
              <p className="mt-0.5 text-xs text-gray-400">
                {new Date(n.created_at).toLocaleString("fr-FR")}
              </p>
            </button>
          ))}

          {hasMore && (
            <button
              onClick={loadMore}
              disabled={loading}
              className="w-full px-4 py-2 text-center text-xs text-blue-600 hover:bg-gray-50 disabled:opacity-50"
            >
              {loading ? "Chargement..." : "Voir plus"}
            </button>
          )}
        </div>
      )}
    </div>
  );
}
