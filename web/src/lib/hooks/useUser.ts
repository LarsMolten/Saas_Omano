"use client";

import { useState, useEffect } from "react";
import type { User } from "@/lib/types/user";

export function useUser() {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);

  useEffect(() => {
    fetch("/api/auth/me")
      .then((res) => {
        if (!res.ok) throw new Error("Not authenticated");
        return res.json();
      })
      .then((data) => setUser(data))
      .catch(() => setError(true))
      .finally(() => setLoading(false));
  }, []);

  return { user, loading, error };
}
