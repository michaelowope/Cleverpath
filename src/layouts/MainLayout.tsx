import AppNav from "@/components/AppNav";
import { Outlet } from "react-router";

export default function MainLayout() {
  return (
    <section className="w-full min-h-[100dvh] relative">
      <AppNav />
      <Outlet />
    </section>
  );
}
