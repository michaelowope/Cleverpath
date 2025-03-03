import { NavLink } from "react-router";
import WhiteFullLogo from "@/assets/full-logo-light.svg";

export default function AppNav() {
  return (
    <nav className="p-5 bg-blue-900 fixed top-0 w-full left-0">
      <div className="max-w-[1440px] w-full m-auto flex justify-between items-center">
        <NavLink to={"/"} className="h-[65px]">
          <img src={WhiteFullLogo} alt="app logo" className="w-full h-full" />
        </NavLink>
        <ul id="navbar" className="flex justify-between gap-2">
          <li>
            <NavLink
              to={"/"}
              className="capitalize text-lg hover:bg-blue-950 px-4 py-2 rounded text-white font-extrabold transition-all"
            >
              Home
            </NavLink>
          </li>
          <li>
            <NavLink
              to={"/about"}
              className="capitalize text-lg hover:bg-blue-950 px-4 py-2 rounded text-white font-extrabold transition-all"
            >
              about
            </NavLink>
          </li>
          <li>
            <NavLink
              to={"/courses"}
              className="capitalize text-lg hover:bg-blue-950 px-4 py-2 rounded text-white font-extrabold transition-all"
            >
              courses
            </NavLink>
          </li>
          <li>
            <NavLink
              to={"/contact"}
              className="capitalize text-lg hover:bg-blue-950 px-4 py-2 rounded text-white font-extrabold transition-all"
            >
              contacts
            </NavLink>
          </li>
          <li>
            <NavLink
              to={"/test"}
              className="capitalize text-lg hover:bg-blue-950 px-4 py-2 rounded text-white font-extrabold transition-all"
            >
              test
            </NavLink>
          </li>
        </ul>
      </div>
    </nav>
  );
}
