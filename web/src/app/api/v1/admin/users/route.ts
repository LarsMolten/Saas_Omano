import { NextRequest, NextResponse } from "next/server";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";

export async function GET(request: NextRequest) {
  const cookie = request.headers.get("cookie") ?? "";
  const url = new URL(request.url);
  const searchParams = url.searchParams.toString();

  const res = await fetch(`${API_URL}/v1/admin/users?${searchParams}`, {
    headers: { Cookie: cookie, Accept: "application/json" },
  });

  const data = await res.json();
  return NextResponse.json(data, { status: res.status });
}
