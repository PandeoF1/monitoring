using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Management;
using System.IO;
using System.Threading;
using MySql.Data.MySqlClient;
using System.Net;
using Echevil;
using System.Timers;
using System.Diagnostics;
using System.Globalization;
using Microsoft.Win32; // Windows Registry
using System.Runtime.InteropServices;
namespace ConsoleApp1
{
    class Program
    {
        static List<string> GetNetworks()
        {
            List<string> networkList = new List<string>();
            PerformanceCounterCategory category = new PerformanceCounterCategory("Network Interface");
            foreach (var name in category.GetInstanceNames())
            {
                networkList.Add(name);
            }
            return networkList;
        }
        static List<string> GetDisks()
        {
            var category = new PerformanceCounterCategory("PhysicalDisk");
            return category.GetInstanceNames().OrderBy(i => i).ToList();
        }
        static Array GetCpuUsage()
        {
            var cpuCounter = new PerformanceCounter("Processor", "% Processor Time", "_Total");
            cpuCounter.NextValue();
            //System.Threading.Thread.Sleep(1000); // wait a second to get a valid reading
            var usage = cpuCounter.NextValue();

            ManagementObjectSearcher searcher = new ManagementObjectSearcher("select * from Win32_PerfFormattedData_PerfOS_Processor");
            var cpuTimes = searcher.Get()
                .Cast<ManagementObject>()
                .Select(mo => new
                {
                    Name = mo["Name"],
                    Usage = mo["PercentProcessorTime"]
                }
                )
                .ToArray();
            return cpuTimes.ToArray();
        }
        static long GetTotalFreeSpace(string driveName)
        {
            foreach (DriveInfo drive in DriveInfo.GetDrives())
            {
                if (drive.IsReady && drive.Name == driveName)
                {
                    return drive.TotalFreeSpace;
                }
            }
            return -1;
        }
        static long GetTotalSize(string driveName)
        {
            foreach (DriveInfo drive in DriveInfo.GetDrives())
            {
                if (drive.IsReady && drive.Name == driveName)
                {
                    return drive.TotalSize;
                }
            }
            return -1;
        }
        [DllImport("user32.dll")]
        static extern bool ShowWindow(IntPtr hWnd, int nCmdShow);
        static void Main(string[] args)
        {
            try
            {
                IntPtr h = Process.GetCurrentProcess().MainWindowHandle;
                ShowWindow(h, 0);


            }
            catch (Exception e)
            {

                //Console.WriteLine("Error: " + e.Message);
            }
            {
                try
                {
                    Process process = new Process();
                    ProcessStartInfo startInfo = new ProcessStartInfo();
                    startInfo.WindowStyle = System.Diagnostics.ProcessWindowStyle.Hidden;
                    startInfo.FileName = "CMD.exe";
                    startInfo.Arguments = "/C wmic csproduct get UUID";
                    process.StartInfo = startInfo;
                    process.StartInfo.UseShellExecute = false;
                    process.StartInfo.RedirectStandardOutput = true;
                    process.Start();
                    process.WaitForExit();
                    string outputt = process.StandardOutput.ReadToEnd();
                    string uuid = outputt;
                    uuid = uuid.Replace("UUID", "").Replace("\n", "").Replace("\r", "").Replace(" ", "");
                    /*------------------------------------------------ SQL ------------------------------------------------*/
                    string cs = @"Data Source=127.0.0.1;Database=monitoring;User Id=user;Password=pass;SSL Mode=None";
                    var db_conn = new MySqlConnection(cs);
                    try
                    {
                        db_conn.Open();

                        try
                        {
                            string sql = "INSERT INTO `hosts` (`uuid`, `hostname`, `ip`) VALUES ('" + uuid + "', '" + Dns.GetHostName() + "', '" + Dns.GetHostByName(Dns.GetHostName()).AddressList[0].ToString() + "')";
                            MySqlCommand cmd = new MySqlCommand(sql, db_conn);
                            cmd.ExecuteNonQuery();
                            cmd = null;
                            //Console.WriteLine("c good");
                        }
                        catch (Exception e)
                        {
                            string sql = "UPDATE `hosts` SET `hostname` = '" + Dns.GetHostName() + "', `ip` = '" + Dns.GetHostByName(Dns.GetHostName()).AddressList[0].ToString() + "' WHERE `hosts`.`uuid` = '" + uuid + "'"; //ici
                            MySqlCommand cmd = new MySqlCommand(sql, db_conn);
                            cmd.ExecuteNonQuery();
                            cmd = null;
                            //Console.WriteLine("Error: " + e.Message);
                        }
                        try
                        {
                            string sql = "CREATE TABLE `log_" + uuid + "` (`id` int(150) NOT NULL,`date` varchar(75) NOT NULL,  `cpu` mediumtext DEFAULT NULL,  `ram` mediumtext DEFAULT NULL,  `disk` mediumtext DEFAULT NULL,  `ethernet` mediumtext DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"; //ici
                            MySqlCommand cmd = new MySqlCommand(sql, db_conn);
                            cmd.ExecuteNonQuery();
                            sql = "ALTER TABLE `log_" + uuid + "` ADD PRIMARY KEY (`id`);"; //ici
                            cmd = new MySqlCommand(sql, db_conn);
                            cmd.ExecuteNonQuery();
                            sql = "ALTER TABLE `log_" + uuid + "` MODIFY `id` int(150) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT = 0"; //ici
                            cmd = new MySqlCommand(sql, db_conn);
                            cmd.ExecuteNonQuery();
                            sql = "COMMIT"; //ici
                            cmd = new MySqlCommand(sql, db_conn);
                            cmd.ExecuteNonQuery();

                        }
                        catch (Exception e)
                        {

                            //Console.WriteLine("Error: " + e.Message);
                        }

                    }
                    catch (Exception e)
                    {
                        //Console.WriteLine("Error: " + e.Message);
                    }

                    Thread.Sleep(100);


                    try
                    {
                        while (true)
                        {


                            DriveInfo[] allDrives = DriveInfo.GetDrives();
                            string disk_usage = null;
                            foreach (DriveInfo d in allDrives)
                            {
                                if (d.IsReady == true)
                                {
                                    if (d.DriveType.ToString() == "Fixed") //Pour ne pas prendre les disk sur le réseau et cd ect ect
                                    {
                                        disk_usage += d.Name + "|" + d.TotalFreeSpace + "|" + d.TotalSize + "|%";
                                    }
                                }
                            }
                            DateTime startTime, endTime;
                            startTime = DateTime.Now;

                            string cpu_usage = null;
                            string ram_usage = null;

                            foreach (var cpu_parse in GetCpuUsage())
                            {
                                string[] cpu_parsed = (cpu_parse.ToString().Split(','));
                                cpu_usage += cpu_parsed[0].Replace("{ Name = ", "").Replace(" Usage = ", "").Replace("}", "").Replace(" ", "") + "|";
                                cpu_usage += cpu_parsed[1].Replace("{ Name = ", "").Replace(" Usage = ", "").Replace("}", "").Replace(" ", "") + "|%";
                            }
                            ObjectQuery wql = new ObjectQuery("SELECT * FROM Win32_OperatingSystem");
                            ManagementObjectSearcher searcher = new ManagementObjectSearcher(wql);
                            ManagementObjectCollection results = searcher.Get();

                            foreach (ManagementObject result in results)
                            {
                                ram_usage += result["TotalVisibleMemorySize"] + "|";
                                ram_usage += result["FreePhysicalMemory"] + "";
                            }

                            NetworkMonitor monitor = new NetworkMonitor();
                            NetworkAdapter[] adapters = monitor.Adapters;
                            monitor.StartMonitoring();
                            double DownloadSpeed = 0;
                            double UploadSpeed = 0;
                            for (int i = 0; i < 25; i++)
                            {
                                foreach (NetworkAdapter adapter in adapters)
                                {
                                    DownloadSpeed += adapter.DownloadSpeedKbps;
                                    UploadSpeed += adapter.UploadSpeedKbps;
                                }
                                System.Threading.Thread.Sleep(1000);
                            }
                            monitor.StopMonitoring();
                            endTime = DateTime.Now;
                            Double elapsedMillisecs = ((TimeSpan)(endTime - startTime)).TotalMilliseconds;
                            //Console.WriteLine(elapsedMillisecs);
                            string network_usage = DownloadSpeed + "|" + UploadSpeed;
                            //Console.WriteLine("CPU : " + cpu_usage);
                            //Console.WriteLine("RAM : " + ram_usage);
                            //Console.WriteLine("Disk : " + disk_usage);
                            //Console.WriteLine("Network : " + network_usage);

                            DateTime localDate = DateTime.Now;
                            DateTime utcDate = DateTime.UtcNow;
                            String[] cultureNames = { "fr-FR" };
                            string date = null;
                            foreach (var cultureName in cultureNames)
                            {
                                var culture = new CultureInfo(cultureName);
                                date = localDate.ToString(culture);
                            }
                            string sql = "INSERT INTO `log_" + uuid + "` (`date`, `cpu`, `ram`, `disk`, `ethernet`) VALUES ('" + date + "', '" + cpu_usage + "', '" + ram_usage + "', '" + disk_usage + "', '" + network_usage + "');";
                            MySqlCommand cmd = new MySqlCommand(sql, db_conn);
                            cmd.ExecuteNonQuery();
                            cmd = null;
                            elapsedMillisecs = 15000 - elapsedMillisecs;
                            if (elapsedMillisecs > 0) System.Threading.Thread.Sleep((int)elapsedMillisecs);
                            endTime = DateTime.Now;
                            elapsedMillisecs = ((TimeSpan)(endTime - startTime)).TotalMilliseconds;
                            //Console.WriteLine(elapsedMillisecs);
                            //Console.WriteLine("---------");
                        }
                    }
                    catch (Exception e)
                    {
                        //Console.WriteLine("Error: " + e.Message);
                    }
                }
                catch (Exception e)
                {

                    //Console.WriteLine("Error: " + e.Message);
                }
            }
            Console.ReadLine();
        }

    }
}
