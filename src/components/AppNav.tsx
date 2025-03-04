import { NavLink } from "react-router";
import WhiteFullLogo from "@/assets/full-logo-light.svg";
import { Menu } from "lucide-react";
import { Sheet, SheetContent, SheetTrigger } from "./ui/sheet";

export default function AppNav() {
  const navLinks = [
    {
      label: "home",
      to: "/",
    },
    {
      label: "about",
      to: "/about",
    },
    {
      label: "courses",
      to: "/courses",
    },
    {
      label: "contact",
      to: "/contact",
    },
    {
      label: "test",
      to: "/test",
    },
  ];

  return (
    <nav className="p-5 bg-blue-900 fixed top-0 w-full left-0">
      <div className="max-w-[1440px] w-full m-auto flex justify-between items-center">
        <NavLink to={"/"} className="h-[65px]">
          <img src={WhiteFullLogo} alt="app logo" className="w-full h-full" />
        </NavLink>
        <ul id="navbar" className="hidden sm:flex justify-between gap-1">
          {navLinks.map((navLink) => (
            <li>
              <NavLink
                to={navLink.to}
                className="capitalize text-lg hover:bg-blue-950 px-4 py-2 rounded text-white font-extrabold transition-all"
              >
                {navLink.label}
              </NavLink>
            </li>
          ))}
        </ul>
        <Sheet>
          <SheetTrigger className="sm:hidden">
            <button className="sm:hidden block cursor-pointer">
              <Menu className="text-white" size={50} />
            </button>
          </SheetTrigger>
          <SheetContent></SheetContent>
        </Sheet>
      </div>
    </nav>
  );
}
