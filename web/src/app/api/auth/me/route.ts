import { NextResponse } from "next/server";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";

export async function GET(request: Request) {
  const cookie = request.headers.get("cookie") ?? "";

  try {
    const res = await fetch(`${API_URL}/v1/auth/me`, {
      headers: { cookie, Accept: "application/json" },
    });
    const data = await res.json();
    return NextResponse.json(data, { status: res.status });
  } catch {
    return NextResponse.json(
      { message: "Non authentifie." },
      { status: 401 }
    );
  }
}
